<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Report-only Content Security Policy and its /csp-report endpoint (#2166).
 */
class CspReportOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_pages_send_a_report_only_policy(): void
    {
        $policy = $this->get('/')->assertOk()->headers->get('Content-Security-Policy-Report-Only');

        $this->assertNotNull($policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString('report-uri /csp-report', $policy);
        // CSP only allows a wildcard at the start of a host
        $this->assertDoesNotMatchRegularExpression('#://[a-z0-9-]+\*#i', $policy);
    }

    public function test_policy_can_be_switched_off(): void
    {
        config(['csp.enabled' => false]);

        $this->assertNull($this->get('/')->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_reports_are_logged_with_trimmed_fields(): void
    {
        $log = Log::spy();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => [
                'document-uri' => 'https://arcane.city/events',
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://evil.example/'.str_repeat('x', 1000),
            ],
        ]))->assertNoContent();

        $log->shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
            return $message === 'CSP violation'
                && $context['directive'] === 'script-src'
                && strlen($context['blocked']) <= 303;
        });
    }

    public function test_junk_reports_are_accepted_but_not_logged(): void
    {
        $log = Log::spy();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json')->assertNoContent();

        $log->shouldNotHaveReceived('warning');
    }

    public function test_only_the_report_endpoint_skips_csrf(): void
    {
        $middleware = $this->app->make(VerifyCsrfToken::class);
        $inExcept = (new \ReflectionMethod($middleware, 'inExceptArray'))->getClosure($middleware);

        $this->assertTrue($inExcept(Request::create('/csp-report', 'POST')));
        $this->assertFalse($inExcept(Request::create('/tags/1/follow', 'POST')));
    }
}
