<?php
/**
 * Favicon Discoverer Class
 * Handles discovering and fetching favicons from websites
 */

class FaviconDiscoverer {
    private $maxFaviconSize;
    private $userAgent;
    private $timeout;
    private $debug;
    private $debugLog = [];
    
    public function __construct($maxFaviconSize = 32, $userAgent = 'PHP Favicon Discoverer', $timeout = 10, $debug = false) {
        $this->maxFaviconSize = $maxFaviconSize;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
        $this->debug = $debug;
        
        if ($this->debug) {
            $this->addDebugLog('constructor', 'FaviconDiscoverer initialized', [
                'maxFaviconSize' => $maxFaviconSize,
                'userAgent' => $userAgent,
                'timeout' => $timeout,
                'debug' => $debug
            ]);
        }
    }
    
    /**
     * Get debug log
     */
    public function getDebugLog() {
        return $this->debugLog;
    }
    
    /**
     * Clear debug log
     */
    public function clearDebugLog() {
        $this->debugLog = [];
    }
    
    /**
     * Add debug entry
     */
    private function addDebugLog($step, $message, $data = null) {
        if ($this->debug) {
            $this->debugLog[] = [
                'step' => $step,
                'message' => $message,
                'data' => $data,
                'timestamp' => microtime(true)
            ];
        }
    }
    
    /**
     * Get the best favicon URL for a given site URL
     */
    public function getFaviconUrl($siteUrl) {
        $this->addDebugLog('getFaviconUrl', 'Starting favicon discovery', ['siteUrl' => $siteUrl]);
        
        $icons = $this->discoverFavicons($siteUrl);
        
        $this->addDebugLog('getFaviconUrl', 'Discovered icons from HTML', [
            'count' => count($icons),
            'icons' => $icons
        ]);
        
        // If we found explicit icons in HTML, try them (largest first)
        if (!empty($icons)) {
            usort($icons, [$this, 'compareIcons']);
            $this->addDebugLog('getFaviconUrl', 'Sorted icons by size', ['sorted_icons' => $icons]);
            
            foreach ($icons as $icon) {
                $this->addDebugLog('getFaviconUrl', 'Testing icon URL', ['icon' => $icon]);
                if ($this->urlExists($icon['href'])) {
                    $this->addDebugLog('getFaviconUrl', 'Found working icon from HTML', ['favicon_url' => $icon['href']]);
                    return $icon['href'];
                } else {
                    $this->addDebugLog('getFaviconUrl', 'Icon URL failed', ['icon_url' => $icon['href']]);
                }
            }
        }
        
        // Fallbacks in common locations
        $base = $this->getOrigin($siteUrl);
        $this->addDebugLog('getFaviconUrl', 'Trying fallback locations', ['base' => $base]);
        
        $fallbacks = [
            '/favicon.ico',
            '/favicon.png',
            '/apple-touch-icon.png',
            '/apple-touch-icon-precomposed.png',
            '/favicon-32x32.png',
            '/favicon-16x16.png',
        ];
        
        foreach ($fallbacks as $path) {
            $url = rtrim($base, '/') . $path;
            $this->addDebugLog('getFaviconUrl', 'Testing fallback URL', ['fallback_url' => $url]);
            
            if ($this->urlExists($url)) {
                $this->addDebugLog('getFaviconUrl', 'Found working fallback', ['favicon_url' => $url]);
                return $url;
            } else {
                $this->addDebugLog('getFaviconUrl', 'Fallback URL failed', ['fallback_url' => $url]);
            }
        }
        
        $this->addDebugLog('getFaviconUrl', 'No favicon found', ['siteUrl' => $siteUrl]);
        
        return $this->getDefaultFavicon();
    }
    
    /**
     * Get default SVG favicon representing the world wide web
     */
    public function getDefaultFavicon() {
        $defaultFavicon = 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#4A90E2"/>
            <text x="16" y="22" font-family="Arial, sans-serif" font-size="18" text-anchor="middle" fill="white">🔗</text>
            </svg>');
        
        $this->addDebugLog('getFaviconUrl', 'Returning default SVG favicon', ['default_favicon' => $defaultFavicon]);
        return $defaultFavicon;
    }
    
    /**
     * Discover icon candidates by parsing the HTML <link> tags
     */
    private function discoverFavicons($siteUrl) {
        $this->addDebugLog('discoverFavicons', 'Starting HTML favicon discovery', ['siteUrl' => $siteUrl]);
        
        $siteUrl = $this->normalizeUrl($siteUrl);
        $this->addDebugLog('discoverFavicons', 'Normalized URL', ['normalized_url' => $siteUrl]);
        
        $html = $this->httpGet($siteUrl);
        if ($html === null) {
            $this->addDebugLog('discoverFavicons', 'Failed to get HTML content', ['siteUrl' => $siteUrl]);
            return [];
        }
        
        $this->addDebugLog('discoverFavicons', 'Retrieved HTML content', ['html_length' => strlen($html)]);
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            $this->addDebugLog('discoverFavicons', 'Failed to parse HTML', ['siteUrl' => $siteUrl]);
            return [];
        }
        libxml_clear_errors();
        
        $this->addDebugLog('discoverFavicons', 'Successfully parsed HTML DOM');
        
        $xpath = new DOMXPath($dom);
        $rels = [
            'icon', 'shortcut icon', 'apple-touch-icon', 'apple-touch-icon-precomposed',
            'mask-icon', 'fluid-icon', 'alternate icon'
        ];
        
        $this->addDebugLog('discoverFavicons', 'Searching for icon rel types', ['rel_types' => $rels]);
        
        $queryParts = [];
        foreach ($rels as $r) {
            $queryParts[] = sprintf("//link[contains(translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '%s')]", strtolower($r));
        }
        $nodes = $xpath->query(implode(' | ', $queryParts));
        
        $this->addDebugLog('discoverFavicons', 'Found link nodes', ['node_count' => $nodes->length]);
        
        $origin = $this->getOrigin($siteUrl);
        $baseHref = $this->getBaseHref($dom, $siteUrl);
        
        $this->addDebugLog('discoverFavicons', 'URL resolution info', [
            'origin' => $origin,
            'siteUrl' => $siteUrl,
            'baseHref' => $baseHref
        ]);
        
        $icons = [];
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            if (!$href) {
                $this->addDebugLog('discoverFavicons', 'Link node has no href', ['node' => $node->nodeName]);
                continue;
            }
            
            $this->addDebugLog('discoverFavicons', 'Processing link node', [
                'href' => $href,
                'rel' => $node->getAttribute('rel'),
                'sizes' => $node->getAttribute('sizes'),
                'type' => $node->getAttribute('type')
            ]);
            
            $abs = $this->resolveUrl($baseHref, $href);
            if (!$abs) {
                $this->addDebugLog('discoverFavicons', 'Failed to resolve URL', ['href' => $href, 'baseHref' => $baseHref]);
                continue;
            }
            
            $this->addDebugLog('discoverFavicons', 'Resolved absolute URL', ['absolute_url' => $abs]);
            
            $icons[] = [
                'href'  => $abs,
                'rel'   => strtolower(trim($node->getAttribute('rel'))),
                'sizes' => strtolower(trim($node->getAttribute('sizes'))),
                'type'  => strtolower(trim($node->getAttribute('type'))),
            ];
        }
        
        // De-duplicate by URL
        $uniq = [];
        $out = [];
        foreach ($icons as $i) {
            $key = $i['href'];
            if (!isset($uniq[$key])) {
                $uniq[$key] = true;
                $out[] = $i;
            }
        }
        
        $this->addDebugLog('discoverFavicons', 'Completed icon discovery', ['icon_count' => count($out)]);
        return $out;
    }
    
    /**
     * Compare icons by desirability: prefer SVG, then largest numeric size, then PNG/ICO
     */
    private function compareIcons($a, $b) {
        $scoreA = $this->iconScore($a);
        $scoreB = $this->iconScore($b);
        return $scoreB <=> $scoreA;
    }
    
    /**
     * Score an icon based on type, size, and format preferences
     */
    private function iconScore($i) {
        // Prefer SVG (mask-icon or type svg)
        $isSvg = (strpos($i['type'] ?? '', 'svg') !== false) || (strpos($i['rel'] ?? '', 'mask-icon') !== false);
        $score = $isSvg ? 1000 : 0;
        
        // Parse sizes: may be like "16x16" or "32x32 48x48" or "any"
        $sizesStr = $i['sizes'] ?? '';
        $maxArea = 0;
        
        if ($sizesStr === 'any') {
            $maxArea = $this->maxFaviconSize * $this->maxFaviconSize;
        } else {
            foreach (preg_split('/\s+/', trim($sizesStr)) as $token) {
                if (preg_match('/^(\d+)x(\d+)$/', $token, $m)) {
                    $width = (int)$m[1];
                    $height = (int)$m[2];
                    $area = $width * $height;
                    
                    // Only consider sizes up to the maximum
                    if ($width <= $this->maxFaviconSize && $height <= $this->maxFaviconSize && $area > $maxArea) {
                        $maxArea = $area;
                    }
                }
            }
        }
        
        // If no sizes given, give a modest base
        if ($maxArea === 0) $maxArea = 16 * 16;
        
        $score += $maxArea / 10.0;
        
        // Slight preference for PNG over ICO if sizes tie
        if (strpos($i['type'] ?? '', 'png') !== false) $score += 1.5;
        if (strpos($i['type'] ?? '', 'ico') !== false) $score += 1.0;
        
        return $score;
    }
    
    /**
     * Check if URL exists and returns an image
     */
    private function urlExists($url) {
        $this->addDebugLog('urlExists', 'Checking URL existence', ['url' => $url]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY        => true,
            CURLOPT_FOLLOWLOCATION=> true,
            CURLOPT_MAXREDIRS     => 5,
            CURLOPT_TIMEOUT       => $this->timeout,
            CURLOPT_USERAGENT     => $this->userAgent,
            CURLOPT_RETURNTRANSFER=> true,
            CURLOPT_HEADER        => true,
        ]);
        $ok = curl_exec($ch) !== false;
        $code = $ok ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
        $contentType = $ok ? curl_getinfo($ch, CURLINFO_CONTENT_TYPE) : '';
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $this->addDebugLog('urlExists', 'HEAD request result', [
            'url' => $url,
            'success' => $ok,
            'http_code' => $code,
            'content_type' => $contentType,
            'curl_error' => $curlError
        ]);
        
        if ($ok && $code >= 200 && $code < 300) {
            $contentType = strtolower($contentType);
            if (strpos($contentType, 'image/') === 0 || 
                strpos($contentType, 'text/html') === false) {
                $this->addDebugLog('urlExists', 'URL exists and is valid', [
                    'url' => $url,
                    'content_type' => $contentType
                ]);
                return true;
            } else {
                $this->addDebugLog('urlExists', 'URL exists but wrong content type', [
                    'url' => $url,
                    'content_type' => $contentType
                ]);
            }
        }
        
        // Some servers don't like HEAD; try GET quickly
        $this->addDebugLog('urlExists', 'Trying GET request as fallback', ['url' => $url]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY        => false,
            CURLOPT_FOLLOWLOCATION=> true,
            CURLOPT_MAXREDIRS     => 5,
            CURLOPT_TIMEOUT       => $this->timeout,
            CURLOPT_USERAGENT     => $this->userAgent,
            CURLOPT_RETURNTRANSFER=> true,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $this->addDebugLog('urlExists', 'GET request result', [
            'url' => $url,
            'success' => $resp !== false,
            'http_code' => $code,
            'content_type' => $contentType,
            'response_length' => $resp ? strlen($resp) : 0,
            'curl_error' => $curlError
        ]);
        
        if (($resp !== false) && $code >= 200 && $code < 300) {
            $contentType = strtolower($contentType);
            if (strpos($contentType, 'image/') === 0 || 
                strpos($contentType, 'text/html') === false) {
                $this->addDebugLog('urlExists', 'GET request successful', [
                    'url' => $url,
                    'content_type' => $contentType
                ]);
                return true;
            } else {
                $this->addDebugLog('urlExists', 'GET request successful but wrong content type', [
                    'url' => $url,
                    'content_type' => $contentType
                ]);
            }
        }
        
        $this->addDebugLog('urlExists', 'URL does not exist or is invalid', ['url' => $url]);
        return false;
    }
    
    /**
     * HTTP GET request
     */
    public function httpGet($url) {
        $this->addDebugLog('httpGet', 'Starting HTTP GET request', ['url' => $url]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION=> true,
            CURLOPT_MAXREDIRS     => 5,
            CURLOPT_CONNECTTIMEOUT=> 8,
            CURLOPT_TIMEOUT       => $this->timeout,
            CURLOPT_USERAGENT     => $this->userAgent,
            CURLOPT_RETURNTRANSFER=> true,
            CURLOPT_ENCODING      => '',
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        $this->addDebugLog('httpGet', 'HTTP GET result', [
            'url' => $url,
            'success' => $res !== false,
            'http_code' => $code,
            'response_length' => $res ? strlen($res) : 0,
            'curl_error' => $curlError,
            'final_url' => $finalUrl
        ]);
        
        if ($res === false || $code < 200 || $code >= 400) {
            $this->addDebugLog('httpGet', 'HTTP GET failed', [
                'url' => $url,
                'http_code' => $code,
                'curl_error' => $curlError
            ]);
            return null;
        }
        
        $this->addDebugLog('httpGet', 'HTTP GET successful', [
            'url' => $url,
            'content_length' => strlen($res)
        ]);
        return $res;
    }
    
    /**
     * Normalize URL (add https if missing)
     */
    private function normalizeUrl($url) {
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return $url;
    }
    
    /**
     * Get origin from URL
     */
    private function getOrigin($url) {
        $p = parse_url($this->normalizeUrl($url));
        $scheme = $p['scheme'] ?? 'https';
        $host   = $p['host']   ?? '';
        $port   = isset($p['port']) ? ':' . $p['port'] : '';
        return strtolower($scheme . '://' . $host . $port);
    }
    
    /**
     * Get base href from DOM
     */
    private function getBaseHref($dom, $siteUrl) {
        $bases = $dom->getElementsByTagName('base');
        if ($bases->length > 0) {
            $href = $bases->item(0)->getAttribute('href');
            if ($href) {
                $origin = $this->getOrigin($siteUrl);
                $resolved = $this->resolveUrl($origin, $href);
                if ($resolved) return $resolved;
            }
        }
        return $siteUrl;
    }
    
    /**
     * Resolve relative URL against base URL
     */
    private function resolveUrl($base, $rel) {
        $this->addDebugLog('resolveUrl', 'Resolving relative URL', [
            'base' => $base,
            'relative' => $rel
        ]);
        
        // Already absolute?
        if (preg_match('~^https?://~i', $rel)) {
            $this->addDebugLog('resolveUrl', 'URL is already absolute', ['url' => $rel]);
            return $rel;
        }
        
        // Protocol-relative: //example.com/...
        if (strpos($rel, '//') === 0) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            $result = $scheme . ':' . $rel;
            $this->addDebugLog('resolveUrl', 'Protocol-relative URL resolved', ['result' => $result]);
            return $result;
        }
        
        $bp = parse_url($base);
        if (!$bp || empty($bp['scheme']) || empty($bp['host'])) {
            $this->addDebugLog('resolveUrl', 'Invalid base URL', ['base' => $base]);
            return null;
        }
        
        $scheme = $bp['scheme'];
        $host   = $bp['host'];
        $port   = isset($bp['port']) ? ':' . $bp['port'] : '';
        $path   = $bp['path'] ?? '/';
        
        $this->addDebugLog('resolveUrl', 'Parsed base URL components', [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'path' => $path
        ]);
        
        // Root-relative
        if (strpos($rel, '/') === 0) {
            $path = $rel;
            $this->addDebugLog('resolveUrl', 'Root-relative URL', ['path' => $path]);
        } else {
            // Directory of base
            $dir = preg_replace('~/[^/]*$~', '/', $path);
            $path = $dir . $rel;
            $this->addDebugLog('resolveUrl', 'Relative URL in directory', ['dir' => $dir, 'path' => $path]);
        }
        
        // Normalize ../ and ./
        $segments = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') array_pop($segments);
            else $segments[] = $seg;
        }
        $normPath = '/' . implode('/', $segments);
        
        $result = $scheme . '://' . $host . $port . $normPath;
        $this->addDebugLog('resolveUrl', 'Resolved URL', ['result' => $result]);
        return $result;
    }

    /**
     * Get debug log summary
     */
    public function getDebugSummary() {
        $summary = [
            'total_steps' => count($this->debugLog),
            'steps' => [],
            'errors' => [],
            'success' => false,
            'final_result' => null
        ];
        
        foreach ($this->debugLog as $log) {
            $summary['steps'][] = $log['step'];
            
            // Check for errors
            if (strpos(strtolower($log['message']), 'failed') !== false || 
                strpos(strtolower($log['message']), 'error') !== false) {
                $summary['errors'][] = [
                    'step' => $log['step'],
                    'message' => $log['message'],
                    'data' => $log['data']
                ];
            }
            
            // Check for success
            if (strpos(strtolower($log['message']), 'found working') !== false) {
                $summary['success'] = true;
                $summary['final_result'] = $log['data']['favicon_url'] ?? null;
            }
        }
        
        return $summary;
    }
    
    /**
     * Export debug log as JSON
     */
    public function exportDebugLog() {
        return json_encode($this->debugLog, JSON_PRETTY_PRINT);
    }
}
?> 