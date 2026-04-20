<?php
/**
 * Favicon Configuration
 * Shared helpers for generated placeholders and favicon URL normalization.
 */

class FaviconConfig {
    private const PLACEHOLDER_PALETTE = [
        ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
        ['bg' => '#dcfce7', 'fg' => '#166534'],
        ['bg' => '#fef3c7', 'fg' => '#92400e'],
        ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
        ['bg' => '#ede9fe', 'fg' => '#6d28d9'],
        ['bg' => '#cffafe', 'fg' => '#155e75'],
        ['bg' => '#fce7f3', 'fg' => '#be185d'],
        ['bg' => '#e0f2fe', 'fg' => '#0369a1'],
    ];

    /**
     * Get the generic default favicon as a data URI.
     */
    public static function getDefaultFaviconDataUri() {
        return self::buildSvgDataUri('?', '#f0f0f0', '#333333');
    }

    /**
     * Get the default favicon as a base64 string.
     */
    public static function getDefaultFaviconBase64() {
        return substr(self::getDefaultFaviconDataUri(), strlen('data:image/svg+xml;base64,'));
    }

    /**
     * Get a deterministic generated favicon for a host or URL.
     */
    public static function getGeneratedFaviconDataUri($hostOrUrl = '') {
        $host = self::extractHost($hostOrUrl);
        if ($host === '') {
            return self::getDefaultFaviconDataUri();
        }

        $palette = self::PLACEHOLDER_PALETTE[abs(crc32($host)) % count(self::PLACEHOLDER_PALETTE)];
        $label = self::getPlaceholderLabel($host);

        return self::buildSvgDataUri($label, $palette['bg'], $palette['fg']);
    }

    /**
     * Get an external favicon fallback for hosts that block server-side fetching.
     */
    public static function getExternalFallbackFaviconUrl($hostOrUrl = '') {
        $normalizedUrl = trim((string)$hostOrUrl);
        if ($normalizedUrl === '') {
            return '';
        }

        if (!preg_match('~^https?://~i', $normalizedUrl)) {
            $normalizedUrl = 'https://' . ltrim($normalizedUrl, '/');
        }

        return 'https://www.google.com/s2/favicons?sz=64&domain_url=' . rawurlencode($normalizedUrl);
    }

    /**
     * Check if a favicon URL is the generic default favicon.
     */
    public static function isDefaultFavicon($faviconUrl) {
        if (!$faviconUrl) {
            return true;
        }

        return trim((string)$faviconUrl) === self::getDefaultFaviconDataUri();
    }

    /**
     * Normalize stored favicon values so the database keeps canonical cache paths.
     */
    public static function normalizeStoredFaviconUrl($faviconUrl) {
        $faviconUrl = trim((string)$faviconUrl);
        if ($faviconUrl === '') {
            return '';
        }

        if (strpos($faviconUrl, 'data:image/') === 0) {
            return $faviconUrl;
        }

        $normalizedCachePath = self::normalizeCacheFaviconPath($faviconUrl);
        if ($normalizedCachePath !== '') {
            return $normalizedCachePath;
        }

        if (preg_match('~^https?://~i', $faviconUrl)) {
            $path = parse_url($faviconUrl, PHP_URL_PATH) ?? '';
            $marker = '/cache/favicons/';
            $markerPos = strpos($path, $marker);
            if ($markerPos !== false) {
                return self::normalizeCacheFaviconPath(
                    'cache/favicons/' . substr($path, $markerPos + strlen($marker))
                );
            }

            return $faviconUrl;
        }

        return '';
    }

    /**
     * Convert a stored favicon value into a display-friendly URL.
     */
    public static function getDisplayFaviconUrl($faviconUrl, $bookmarkUrl = '', $relativePrefix = '../') {
        $normalized = self::getRenderableStoredFaviconUrl($faviconUrl);
        if ($normalized === '') {
            return self::getExternalFallbackFaviconUrl($bookmarkUrl) ?: self::getGeneratedFaviconDataUri($bookmarkUrl);
        }

        if (strpos($normalized, 'cache/') === 0) {
            return rtrim($relativePrefix, '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    /**
     * Return a stored favicon value only if it is renderable in the current app.
     */
    public static function getRenderableStoredFaviconUrl($faviconUrl) {
        $normalized = self::normalizeStoredFaviconUrl($faviconUrl);
        if ($normalized === '') {
            return '';
        }

        if (strpos($normalized, 'cache/') === 0 && !self::cachedFaviconFileExists($normalized)) {
            return '';
        }

        return $normalized;
    }

    /**
     * Get favicon configuration for JavaScript.
     */
    public static function getConfigForJavaScript() {
        return [
            'defaultFaviconDataUri' => self::getDefaultFaviconDataUri(),
            'defaultFaviconBase64' => self::getDefaultFaviconBase64(),
            'defaultFaviconAlt' => '?',
            'placeholderPalette' => self::PLACEHOLDER_PALETTE,
        ];
    }

    private static function buildSvgDataUri($label, $background, $foreground) {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES);
        $svg = '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">'
            . '<rect width="32" height="32" rx="6" fill="' . $background . '"/>'
            . '<text x="16" y="21" font-family="Arial, sans-serif" font-size="16" font-weight="700" text-anchor="middle" fill="' . $foreground . '">' . $safeLabel . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function extractHost($hostOrUrl) {
        $value = trim((string)$hostOrUrl);
        if ($value === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $value)) {
            return strtolower(parse_url($value, PHP_URL_HOST) ?? '');
        }

        return strtolower($value);
    }

    private static function normalizeCacheFaviconPath($faviconUrl) {
        $normalized = trim((string)$faviconUrl);
        if ($normalized === '') {
            return '';
        }

        if (strpos($normalized, '../cache/') === 0) {
            $normalized = substr($normalized, 3);
        } elseif (strpos($normalized, '/cache/') === 0) {
            $normalized = ltrim($normalized, '/');
        }

        if (strpos($normalized, 'cache/') !== 0) {
            return '';
        }

        if (!preg_match('~^cache/favicons/[a-z0-9._-]+\.(ico|png|jpe?g|gif|svg|webp)$~i', $normalized)) {
            return '';
        }

        return $normalized;
    }

    private static function cachedFaviconFileExists($faviconUrl) {
        $cachePath = dirname(__DIR__, 2) . '/' . ltrim($faviconUrl, '/');
        return is_file($cachePath);
    }

    private static function getPlaceholderLabel($host) {
        $host = preg_replace('/^www\./i', '', strtolower($host));
        if (preg_match('/[a-z0-9]/i', $host, $matches)) {
            return strtoupper($matches[0]);
        }

        return '?';
    }
}
?>
