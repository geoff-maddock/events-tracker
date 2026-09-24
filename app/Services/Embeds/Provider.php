<?php

namespace App\Services\Embeds;

use DOMDocument;
use DOMXPath;

/**
 * Used to extract data from provider URLs
 */
class Provider
{
    protected string $url;
    protected array $options;
    protected ?string $response = null;

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $response
     */
    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    /**
     * @return string|null $response
     */
    public function getResponse(): ?string
    {
        return $this->response;
    }

    /**
     * @param string $url
     */
    public function request(string $url): void
    {
        if (isset($this->options['response']) && is_string($this->options['response'])) {
            $this->response = $this->options['response'];
        } elseif (null === $this->response) {
            $options = [
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_ENCODING => 'gzip',
                CURLOPT_FAILONERROR => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TCP_FASTOPEN => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'Geoff-Maddock/Events-Tracker BrowserKit',
            ];

            if (isset($this->options['curl']) && is_array($this->options['curl'])) {
                $options = array_replace($options, $this->options['curl']);
            }

            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            curl_close($ch);

            if (false !== $response) {
                $this->response = $response;
            }
        }
    }

    /**
     * Fetch $url over https without following redirects, so the caller can vet each hop.
     * A 2xx body becomes the response; a redirect leaves the response null and returns
     * the absolute target URL. Anything else returns null with a null response.
     */
    public function requestWithoutRedirects(string $url): ?string
    {
        if (isset($this->options['response']) && is_string($this->options['response'])) {
            $this->response = $this->options['response'];

            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Geoff-Maddock/Events-Tracker BrowserKit',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        if ($status >= 300 && $status < 400 && is_string($redirect) && $redirect !== '') {
            return $redirect;
        }

        if ($status >= 200 && $status < 300 && is_string($body)) {
            $this->response = $body;
        }

        return null;
    }

    /**
     * @param string $expression
     * @return string|null
     */
    public function query(string $expression): ?string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;

        if (null !== $this->response && '' !== $this->response) {
            $dom->loadHTML(mb_convert_encoding($this->response, 'HTML-ENTITIES'));
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $string = $xpath->evaluate("string($expression)");

        if (false !== $string && '' !== $string) {
            return $string;
        }

        return null;
    }

    /**
     * @param string $expression
     * @return array|null
     */
    public function xpathQuery(string $expression): ?array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;

        if (null !== $this->response && '' !== $this->response) {
            $dom->loadHTML(mb_convert_encoding($this->response, 'HTML-ENTITIES'));
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $string = $xpath->query($expression);
        $nodes = [];
        foreach ($string as $node) {
            $nodes[] = $node->nodeValue;
        }

        return $nodes;
    }
}
