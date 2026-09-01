<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extracts structured data from an uploaded image using an LLM.
 *
 * The HTTP call and response parsing are identical for every context; only
 * the prompt pair and the response key allowlist differ, and those live in
 * config/ai.php under `prompts.{context}`.
 */
class ImageAnalysisService
{
    /** Analysis contexts with a prompt pair defined in config/ai.php. */
    public const CONTEXTS = ['event', 'entity', 'series'];

    public const DEFAULT_CONTEXT = 'event';

    /**
     * Analyse an image and return the extracted data as an array.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the context is not supported
     * @throws \RuntimeException when the provider, config or response is unusable
     */
    public function analyze(UploadedFile $image, string $context = self::DEFAULT_CONTEXT): array
    {
        if (!in_array($context, self::CONTEXTS, true)) {
            throw new \InvalidArgumentException("Unsupported analysis context: {$context}");
        }

        $provider = config('ai.provider', 'anthropic');

        $extracted = match ($provider) {
            'anthropic' => $this->analyzeWithAnthropic($image, $context),
            default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
        };

        return $this->normalize($extracted, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeWithAnthropic(UploadedFile $image, string $context): array
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
            'model' => config('ai.anthropic.model', 'claude-sonnet-5'),
            'max_tokens' => config('ai.anthropic.max_tokens', 2048),
            'system' => $this->datedPrompt($this->prompt($context, 'system')),
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
                            'text' => $this->prompt($context, 'user'),
                        ],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Anthropic API error', [
                'context' => $context,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('The image could not be analysed. Please try again or contact an administrator.');
        }

        $responseData = $response->json();

        // The response may lead with thinking blocks (adaptive thinking on
        // Sonnet 5 / Opus 5), so take the first text block, not content[0].
        $text = '';
        foreach ($responseData['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text = $block['text'] ?? '';
                break;
            }
        }

        // Strip any markdown fences the model may include despite instructions
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $extracted = json_decode(trim($text), true);

        if (!is_array($extracted)) {
            Log::warning('ImageAnalysisService: could not parse JSON from model response', [
                'context' => $context,
                'text' => $text,
            ]);
            throw new \RuntimeException('The AI returned an unexpected response. Please try again.');
        }

        return $extracted;
    }

    /**
     * Prefix a system prompt with today's date.
     *
     * The model has no clock, so without this it anchors relative dates
     * ("every second Friday", "next Saturday") to an arbitrary point in the
     * year and can return dates in the past. This is computed per request
     * rather than stored in config/ai.php on purpose: config is cached at
     * deploy time, so a baked-in date would go stale between deploys.
     */
    private function datedPrompt(string $prompt): string
    {
        $today = now();

        return 'Today\'s date is ' . $today->toDateString()
            . ' (' . $today->format('l') . '). '
            . 'Interpret every relative or partial date against it, and never return a date in the past '
            . 'for an upcoming or recurring event. '
            . $prompt;
    }

    /**
     * Fetch a prompt string for a context.
     *
     * A missing prompt almost always means a stale cached config after a
     * deploy, so say so rather than sending a null prompt to a paid API.
     */
    private function prompt(string $context, string $which): string
    {
        $prompt = config("ai.prompts.{$context}.{$which}");

        if (!is_string($prompt) || $prompt === '') {
            throw new \RuntimeException(
                "Missing ai.prompts.{$context}.{$which} configuration. "
                . 'If this followed a deploy, run `php artisan config:clear`.'
            );
        }

        return $prompt;
    }

    /**
     * Drop anything the context does not declare, so prompt drift cannot
     * introduce unexpected fields into the form-population payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, string $context): array
    {
        $allowed = config("ai.prompts.{$context}.keys", []);

        if (!is_array($allowed) || $allowed === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($allowed));
    }
}
