<?php
/**
 * Favicon Cache Manager
 * Handles downloading, caching, and serving favicons
 */

class FaviconCache {
    private $cacheDir;
    private $cacheTime;
    
    public function __construct($cacheDir = '../cache/favicons/', $cacheTime = 86400 * 30) {
        $this->cacheDir = $cacheDir;
        $this->cacheTime = $cacheTime; // 30 days default
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get favicon URL (cached or fetch new)
     */
    public function getFaviconUrl($domain) {
        $filename = $this->getCacheFilename($domain);
        $cachePath = $this->cacheDir . $filename;
        
        // Check if we have a valid cached version
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $this->cacheTime) {
            return $this->getCacheUrl($filename);
        }
        
        // Try to fetch and cache the favicon
        return $this->fetchAndCacheFavicon($domain, $cachePath);
    }
    
    /**
     * Force refresh favicon for a domain (ignore cache)
     */
    public function refreshFavicon($domain) {
        $filename = $this->getCacheFilename($domain);
        $cachePath = $this->cacheDir . $filename;
        
        // Delete existing cache if it exists
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
        
        // Fetch and cache the favicon
        return $this->fetchAndCacheFavicon($domain, $cachePath);
    }
    
    /**
     * Fetch favicon from Google's service and cache it
     */
    private function fetchAndCacheFavicon($domain, $cachePath) {
        $googleUrl = "https://www.google.com/s2/favicons?domain=" . urlencode($domain);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (compatible; StartPage/1.0)'
            ]
        ]);
        
        $faviconData = @file_get_contents($googleUrl, false, $context);
        
        if ($faviconData !== false && strlen($faviconData) > 0) {
            // Save to cache
            file_put_contents($cachePath, $faviconData);
            return $this->getCacheUrl($this->getCacheFilename($domain));
        }
        
        // Fallback to Google's service if caching fails
        return $googleUrl;
    }
    
    /**
     * Generate cache filename from domain
     */
    private function getCacheFilename($domain) {
        return preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain) . '.ico';
    }
    
    /**
     * Get URL for cached favicon
     */
    private function getCacheUrl($filename) {
        return 'cache/favicons/' . $filename;
    }
    
    /**
     * Clear expired cache files
     */
    public function cleanupCache() {
        $files = glob($this->cacheDir . '*.ico');
        $now = time();
        
        foreach ($files as $file) {
            if (($now - filemtime($file)) > $this->cacheTime) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        $files = glob($this->cacheDir . '*.ico');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'count' => count($files),
            'size' => $totalSize,
            'size_formatted' => $this->formatBytes($totalSize)
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?> 