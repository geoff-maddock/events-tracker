<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlyerAnalysisService
{
    public function analyze(UploadedFile $image): array
    {
        $provider = config('ai.provider', 'anthropic');

        return match ($provider) {
            'anthropic' => $this->analyzeWithAnthropic($image),
            default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
        };
    }

    private function analyzeWithAnthropic(UploadedFile $image): array
    {
        $apiKey = config('ai.anthropic.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured. Set ANTHROPIC_API_KEY in your .env file.');
        }

        $imageData = base64_encode(file_get_contents($image->getRealPath()));
        $mediaType = $image->getMimeType();

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => config('ai.anthropic.api_version', '2023-06-01'),
            'content-type' => 'application/json',
        ])->timeout(60)->post(config('ai.anthropic.api_url'), [
            'model' => config('ai.anthropic.model', 'claude-opus-4-5'),
            'max_tokens' => config('ai.anthropic.max_tokens', 2048),
            'system' => config('ai.flyer_system_prompt'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $imageData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => config('ai.flyer_user_prompt'),
                        ],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('The flyer could not be analysed. Please try again or contact an administrator.');
        }

        $responseData = $response->json();
        $text = $responseData['content'][0]['text'] ?? '';

        // Strip any markdown fences the model may include despite instructions
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $extracted = json_decode(trim($text), true);

        if (!is_array($extracted)) {
            Log::warning('FlyerAnalysisService: could not parse JSON from model response', ['text' => $text]);
            throw new \RuntimeException('The AI returned an unexpected response. Please try again.');
        }

        return $extracted;
    }
}
