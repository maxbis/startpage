<?php
/**
 * Favicon Cache Manager
 * Handles downloading, caching, and serving favicons
 */

require_once __DIR__ . '/favicon-discoverer.php';

class FaviconCache {
    private $cacheDir;
    private $cacheTime;
    private $faviconDiscoverer;
    private $useDiscoverer;
    
    public function __construct($cacheDir = '../cache/favicons/', $cacheTime = 86400 * 30, $useDiscoverer = false) {
        $this->cacheDir = $cacheDir;
        $this->cacheTime = $cacheTime; // 30 days default
        $this->useDiscoverer = $useDiscoverer;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Initialize favicon discoverer if enabled
        if ($this->useDiscoverer) {
            $this->faviconDiscoverer = new FaviconDiscoverer(32, 'StartPage Favicon Cache');
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
        // Try using FaviconDiscoverer first if enabled
        if ($this->useDiscoverer && $this->faviconDiscoverer) {
            $faviconUrl = $this->fetchWithDiscoverer($domain);
            if ($faviconUrl) {
                return $faviconUrl;
            }
        }
        
        // Fallback to Google's service
        return $this->fetchWithGoogleService($domain, $cachePath);
    }
    
    /**
     * Fetch favicon using FaviconDiscoverer (direct from website)
     */
    private function fetchWithDiscoverer($domain) {
        $siteUrl = "https://{$domain}";
        $faviconUrl = $this->faviconDiscoverer->getFaviconUrl($siteUrl);
        
        if ($faviconUrl) {
            // Download and cache the favicon
            $faviconData = $this->downloadFavicon($faviconUrl);
            if ($faviconData) {
                $filename = $this->getCacheFilename($domain, $faviconUrl);
                $cachePath = $this->cacheDir . $filename;
                file_put_contents($cachePath, $faviconData);
                return $this->getCacheUrl($filename);
            }
        }
        
        return null;
    }
    
    /**
     * Download favicon data from URL
     */
    private function downloadFavicon($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; StartPage/1.0)'
            ]
        ]);
        
        return @file_get_contents($url, false, $context);
    }
    
    /**
     * Fetch favicon from Google's service (original method)
     */
    private function fetchWithGoogleService($domain, $cachePath) {
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
     * Generate cache filename from domain and favicon URL
     */
    private function getCacheFilename($domain, $faviconUrl = null) {
        $baseName = preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain);
        
        // If we have a favicon URL, try to preserve the original extension
        if ($faviconUrl) {
            $pathInfo = pathinfo(parse_url($faviconUrl, PHP_URL_PATH));
            if (isset($pathInfo['extension'])) {
                $extension = strtolower($pathInfo['extension']);
                // Only allow safe image extensions
                if (in_array($extension, ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg'])) {
                    return $baseName . '.' . $extension;
                }
            }
        }
        
        // Fallback to .ico if no valid extension found
        return $baseName . '.ico';
    }
    
    /**
     * Get URL for cached favicon
     */
    private function getCacheUrl($filename) {
        // Convert cache directory path to URL path
        $urlPath = str_replace('../', '', $this->cacheDir);
        return $urlPath . $filename;
    }
    
    /**
     * Clear expired cache files
     */
    public function cleanupCache() {
        // Handle multiple file extensions
        $extensions = ['*.ico', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.svg'];
        $files = [];
        foreach ($extensions as $ext) {
            $files = array_merge($files, glob($this->cacheDir . $ext));
        }
        
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
        // Handle multiple file extensions
        $extensions = ['*.ico', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.svg'];
        $files = [];
        foreach ($extensions as $ext) {
            $files = array_merge($files, glob($this->cacheDir . $ext));
        }
        
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