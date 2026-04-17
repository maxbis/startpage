<?php
/**
 * Favicon Discoverer
 * Compatibility wrapper around the shared icon resolver for debug tools.
 */

require_once __DIR__ . '/icon-resolver.php';

class FaviconDiscoverer {
    private $resolver;
    private $debug;

    public function __construct($maxFaviconSize = 32, $userAgent = 'PHP Favicon Discoverer', $timeout = 10, $debug = false) {
        unset($maxFaviconSize);
        $this->debug = $debug;
        $this->resolver = new IconResolver(dirname(__DIR__, 2) . '/cache/favicons/', 86400 * 30, $userAgent, $timeout, $debug);
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
            CURLOPT_USERAGENT => 'PHP Favicon Fetcher',
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
