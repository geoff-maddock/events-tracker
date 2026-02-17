<?php

namespace App\Helpers;

class BotDetector
{
    /**
     * Common bot/crawler user agent patterns
     */
    private static array $botPatterns = [
        'bot', 'crawl', 'spider', 'slurp', 'scraper', 'curl', 'wget',
        'python', 'java', 'perl', 'ruby', 'go-http', 'httpclient',
        'Googlebot', 'bingbot', 'Yahoo! Slurp', 'DuckDuckBot', 'Baiduspider',
        'YandexBot', 'Sogou', 'Exabot', 'facebot', 'ia_archiver',
        'AhrefsBot', 'SemrushBot', 'DotBot', 'MJ12bot', 'PetalBot',
        'SeekportBot', 'BLEXBot', 'DataForSeoBot', 'Applebot', 'Twitterbot',
        'facebookexternalhit', 'LinkedInBot', 'Slackbot', 'WhatsApp',
        'TelegramBot', 'Discordbot', 'SkypeUriPreview', 'Embedly',
        'Pinterest', 'Tumblr', 'HeadlessChrome', 'PhantomJS', 'Selenium'
    ];

    /**
     * Check if the given user agent is from a bot/crawler
     *
     * @param string|null $userAgent
     * @return bool
     */
    public static function isBot(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        // Convert to lowercase for case-insensitive matching
        $userAgent = strtolower($userAgent);

        // Check against known bot patterns
        foreach (self::$botPatterns as $pattern) {
            if (strpos($userAgent, strtolower($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }
}
