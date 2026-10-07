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
        'Pinterest', 'Tumblr', 'HeadlessChrome', 'PhantomJS', 'Selenium',
        // AI agents and fetchers whose user agent doesn't say "bot" (#2293)
        'ChatGPT-User', 'Claude-User', 'Claude-Web', 'anthropic-ai', 'Perplexity-User',
        'cohere-ai', 'meta-externalagent', 'meta-externalfetcher', 'GoogleOther',
        'Google-Extended', 'MistralAI-User', 'DuckAssist',
    ];

    /**
     * The patterns, lowercased, for matching stored user agents in SQL.
     *
     * @return array<int, string>
     */
    public static function patterns(): array
    {
        return array_values(array_unique(array_map('strtolower', self::$botPatterns)));
    }

    /**
     * Whether a request looks like a person in a browser: real browsers
     * always send a user agent, so an empty one is a script.
     */
    public static function isHuman(?string $userAgent): bool
    {
        return !empty($userAgent) && !self::isBot($userAgent);
    }

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
