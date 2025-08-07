<?php
/**
 * Favicon Configuration
 * Centralized configuration for default favicons and related settings
 */

class FaviconConfig {
    /**
     * Get the default favicon as a data URI
     * This is the single source of truth for the default favicon
     */
    public static function getDefaultFaviconDataUri() {
        $svg = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#f0f0f0"/>
            <text x="16" y="22" font-family="Arial, sans-serif" font-size="18" text-anchor="middle" fill="#333333">🔗</text>
            </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Get the default favicon as a base64 string (without data URI prefix)
     * Useful for JavaScript comparisons
     */
    public static function getDefaultFaviconBase64() {
        $svg = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#f0f0f0"/>
            <text x="16" y="22" font-family="Arial, sans-serif" font-size="18" text-anchor="middle" fill="#333333">🔗</text>
            </svg>';
        
        return base64_encode($svg);
    }
    
    /**
     * Check if a favicon URL is the default favicon
     */
    public static function isDefaultFavicon($faviconUrl) {
        if (!$faviconUrl) return true;
        
        $defaultDataUri = self::getDefaultFaviconDataUri();
        return $faviconUrl === $defaultDataUri;
    }
    
    /**
     * Get favicon configuration as JSON for JavaScript
     */
    public static function getConfigForJavaScript() {
        return [
            'defaultFaviconDataUri' => self::getDefaultFaviconDataUri(),
            'defaultFaviconBase64' => self::getDefaultFaviconBase64(),
            'defaultFaviconAlt' => '🔗'
        ];
    }
}
