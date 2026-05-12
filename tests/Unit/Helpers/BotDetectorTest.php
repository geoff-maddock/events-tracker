<?php

namespace Tests\Unit\Helpers;

use App\Helpers\BotDetector;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    public function test_null_user_agent_is_not_bot(): void
    {
        $this->assertFalse(BotDetector::isBot(null));
    }

    public function test_empty_user_agent_is_not_bot(): void
    {
        $this->assertFalse(BotDetector::isBot(''));
    }

    public function test_regular_browser_is_not_bot(): void
    {
        $chrome = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $this->assertFalse(BotDetector::isBot($chrome));
    }

    public function test_googlebot_is_detected(): void
    {
        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

        $this->assertTrue(BotDetector::isBot($ua));
    }

    public function test_curl_is_detected(): void
    {
        $this->assertTrue(BotDetector::isBot('curl/8.4.0'));
    }

    public function test_detection_is_case_insensitive(): void
    {
        $this->assertTrue(BotDetector::isBot('GOOGLEBOT/2.1'));
    }

    public function test_facebook_crawler_is_detected(): void
    {
        $this->assertTrue(BotDetector::isBot('facebookexternalhit/1.1'));
    }
}
