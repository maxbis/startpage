<?php
/**
 * Favicon Cache Manager
 * Backwards-compatible wrapper around the shared icon resolver.
 */

require_once __DIR__ . '/icon-resolver.php';

class FaviconCache {
    private $resolver;

    public function __construct($cacheDir = '../cache/favicons/', $cacheTime = 86400 * 30, $useDiscoverer = false) {
        unset($useDiscoverer);
        $this->resolver = new IconResolver($cacheDir, $cacheTime, 'StartPage Favicon Cache');
    }

    /**
     * Get a cached or freshly resolved favicon for a bookmark URL.
     */
    public function getFaviconUrl($urlOrDomain) {
        $result = $this->resolver->resolveForUrl($urlOrDomain, false);
        return $result['favicon_url'];
    }

    /**
     * Force refresh the favicon for a bookmark URL.
     */
    public function refreshFavicon($urlOrDomain) {
        $result = $this->resolver->resolveForUrl($urlOrDomain, true);
        return $result['favicon_url'];
    }

    /**
     * Get the full resolver result.
     */
    public function resolveForUrl($urlOrDomain, $forceRefresh = false) {
        return $this->resolver->resolveForUrl($urlOrDomain, $forceRefresh);
    }

    public function cleanupCache() {
        $this->resolver->cleanupCache();
    }

    public function clearCache() {
        return $this->resolver->clearCache();
    }

    public function getCacheStats() {
        return $this->resolver->getCacheStats();
    }

    public function getCachePreviewFiles() {
        return $this->resolver->getCachePreviewFiles();
    }
}
?>
