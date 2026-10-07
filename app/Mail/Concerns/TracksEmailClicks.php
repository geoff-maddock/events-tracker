<?php

namespace App\Mail\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * Routes the site's own links in a mail through /email/click (#2083), so a
 * member who reads their email and clicks through, but never logs in, still
 * counts as engaged and isn't paused by the digest gate.
 *
 * The rewrite runs on the rendered HTML and text, so it covers every link,
 * buttons included, without each template opting in. Left alone: links to
 * other hosts (ticket sites, mailto:) and links that are already signed
 * (unsubscribe, preferences, resume), which have to keep working as they are.
 */
trait TracksEmailClicks
{
    /** The recipient a click is credited to; null leaves links untouched. */
    abstract protected function clickTrackedUser(): ?User;

    /**
     * @param array<string, mixed> $viewData
     */
    protected function buildMarkdownHtml($viewData)
    {
        $render = parent::buildMarkdownHtml($viewData);

        return fn ($data) => new HtmlString($this->trackHtmlLinks((string) $render($data)));
    }

    /**
     * @param array<string, mixed> $viewData
     */
    protected function buildMarkdownText($viewData)
    {
        $render = parent::buildMarkdownText($viewData);

        return fn ($data) => new HtmlString($this->trackTextLinks((string) $render($data)));
    }

    private function trackHtmlLinks(string $html): string
    {
        return (string) preg_replace_callback('/href="([^"]+)"/', function (array $m) {
            $tracked = $this->trackedUrl(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));

            return $tracked === null ? $m[0] : 'href="'.e($tracked).'"';
        }, $html);
    }

    private function trackTextLinks(string $text): string
    {
        return (string) preg_replace_callback('#https?://[^\s<>"()\[\]]+#', function (array $m) {
            // a link at the end of a sentence: keep the full stop out of the URL
            $url = rtrim($m[0], '.,;:!?');
            $tracked = $this->trackedUrl($url);

            return $tracked === null ? $m[0] : $tracked.substr($m[0], strlen($url));
        }, $text);
    }

    /** The tracked link for a site URL, or null to leave it as it is. */
    private function trackedUrl(string $url): ?string
    {
        $user = $this->clickTrackedUser();
        $parts = parse_url($url);
        $siteHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (!$user || !is_array($parts) || !isset($parts['host']) || !is_string($siteHost)
            || strcasecmp($parts['host'], $siteHost) !== 0
            || str_contains($parts['query'] ?? '', 'signature=')) {
            return null;
        }

        $to = ($parts['path'] ?? '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

        return URL::signedRoute('email.click', ['id' => $user->id, 'to' => $to]);
    }
}
