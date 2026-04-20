<?php
/**
 * Favicon Discoverer
 * Compatibility wrapper around the shared icon resolver for debug tools.
 */

require_once __DIR__ . '/icon-resolver.php';

class FaviconDiscoverer {
    private const BROWSER_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36';
    private $resolver;
    private $debug;

    public function __construct($maxFaviconSize = 32, $userAgent = 'PHP Favicon Discoverer', $timeout = 10, $debug = false) {
        unset($maxFaviconSize);
        $this->debug = $debug;
        $effectiveUserAgent = ($userAgent === 'PHP Favicon Discoverer' || trim((string)$userAgent) === '')
            ? self::BROWSER_USER_AGENT
            : $userAgent;
        $this->resolver = new IconResolver(dirname(__DIR__, 2) . '/cache/favicons/', 86400 * 30, $effectiveUserAgent, $timeout, $debug);
    }

    public function getDebugLog() {
        return $this->resolver->getDebugLog();
    }

    public function clearDebugLog() {
        $this->resolver->clearDebugLog();
    }

    public function getDebugSummary() {
        return $this->resolver->getDebugSummary();
    }

    public function exportDebugLog() {
        return $this->resolver->exportDebugLog();
    }

    /**
     * Get the best favicon source for a site URL.
     * Tools expect the original source URL when available.
     */
    public function getFaviconUrl($siteUrl) {
        $result = $this->resolver->resolveForUrl($siteUrl, true);
        return $result['source_url'] ?: $result['favicon_url'];
    }

    /**
     * Expose HTML fetch for debugging tools.
     */
    public function httpGet($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => self::BROWSER_USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,nl;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 400) {
            return null;
        }

        return $body;
    }
}
?>
