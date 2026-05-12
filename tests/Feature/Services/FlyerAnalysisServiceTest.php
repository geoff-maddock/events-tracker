<?php

namespace Tests\Feature\Services;

use App\Services\FlyerAnalysisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlyerAnalysisServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('ai.provider', 'anthropic');
        config()->set('ai.anthropic.api_key', 'test-key');
        config()->set('ai.anthropic.api_url', 'https://api.anthropic.com/v1/messages');
    }

    private function flyer(): UploadedFile
    {
        return UploadedFile::fake()->image('flyer.jpg');
    }

    public function test_returns_decoded_json_payload_on_success(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => '{"name":"Test Event","start_at":"2026-08-15 20:00"}']],
            ], 200),
        ]);

        $result = (new FlyerAnalysisService())->analyze($this->flyer());

        $this->assertSame('Test Event', $result['name']);
        $this->assertSame('2026-08-15 20:00', $result['start_at']);
    }

    public function test_strips_markdown_code_fences_from_model_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => "```json\n{\"name\":\"Fenced Event\"}\n```"]],
            ], 200),
        ]);

        $result = (new FlyerAnalysisService())->analyze($this->flyer());

        $this->assertSame('Fenced Event', $result['name']);
    }

    public function test_api_failure_throws_runtime_exception(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 503),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The flyer could not be analysed');

        (new FlyerAnalysisService())->analyze($this->flyer());
    }

    public function test_unparseable_response_throws_runtime_exception(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'this is not json']],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI returned an unexpected response');

        (new FlyerAnalysisService())->analyze($this->flyer());
    }

    public function test_missing_api_key_throws(): void
    {
        config()->set('ai.anthropic.api_key', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API key is not configured');

        (new FlyerAnalysisService())->analyze($this->flyer());
    }

    public function test_unknown_provider_throws(): void
    {
        config()->set('ai.provider', 'openai');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported AI provider');

        (new FlyerAnalysisService())->analyze($this->flyer());
    }
}
