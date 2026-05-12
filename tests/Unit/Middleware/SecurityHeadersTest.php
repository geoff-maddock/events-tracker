<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_all_expected_headers_are_set(): void
    {
        $middleware = new SecurityHeaders();

        $response = $middleware->handle(Request::create('/'), fn () => new Response('ok'));

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_underlying_response_body_is_preserved(): void
    {
        $middleware = new SecurityHeaders();
        $response = $middleware->handle(Request::create('/'), fn () => new Response('hello world'));

        $this->assertSame('hello world', $response->getContent());
    }
}
