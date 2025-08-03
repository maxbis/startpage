<?php
/**
 * Favicon Discoverer Class
 * Handles discovering and fetching favicons from websites
 */

class FaviconDiscoverer {
    private $maxFaviconSize;
    private $userAgent;
    private $timeout;
    
    public function __construct($maxFaviconSize = 32, $userAgent = 'PHP Favicon Discoverer', $timeout = 10) {
        $this->maxFaviconSize = $maxFaviconSize;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
    }
    
    /**
     * Get the best favicon URL for a given site URL
     */
    public function getFaviconUrl($siteUrl) {
        $icons = $this->discoverFavicons($siteUrl);
        
        // If we found explicit icons in HTML, try them (largest first)
        if (!empty($icons)) {
            usort($icons, [$this, 'compareIcons']);
            foreach ($icons as $icon) {
                if ($this->urlExists($icon['href'])) {
                    return $icon['href'];
                }
            }
        }
        
        // Fallbacks in common locations
        $base = $this->getOrigin($siteUrl);
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
            if ($this->urlExists($url)) {
                return $url;
            }
        }
        
        return $this->getDefaultFavicon();
    }
    
    /**
     * Get default SVG favicon representing the world wide web
     */
    public function getDefaultFavicon() {
        return 'data:image/svg+xml;base64,' . base64_encode('
<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="16" cy="16" r="15" fill="#4A90E2" stroke="#2C5AA0" stroke-width="2"/>
  <path d="M16 1C7.716 1 1 7.716 1 16s6.716 15 15 15 15-6.716 15-15S24.284 1 16 1z" fill="none" stroke="#2C5AA0" stroke-width="2"/>
  <path d="M1 16h30M16 1c5.523 0 10 4.477 10 10s-4.477 10-10 10S6 26.523 6 21s4.477-10 10-10z" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"/>
  <circle cx="16" cy="16" r="3" fill="#FFFFFF"/>
  <path d="M16 13v6M13 16h6" stroke="#4A90E2" stroke-width="1.5" stroke-linecap="round"/>
</svg>');
    }
    
    /**
     * Discover icon candidates by parsing the HTML <link> tags
     */
    private function discoverFavicons($siteUrl) {
        $siteUrl = $this->normalizeUrl($siteUrl);
        $html = $this->httpGet($siteUrl);
        if ($html === null) return [];
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            return [];
        }
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $rels = [
            'icon', 'shortcut icon', 'apple-touch-icon', 'apple-touch-icon-precomposed',
            'mask-icon', 'fluid-icon', 'alternate icon'
        ];
        
        $queryParts = [];
        foreach ($rels as $r) {
            $queryParts[] = sprintf("//link[contains(translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '%s')]", strtolower($r));
        }
        $nodes = $xpath->query(implode(' | ', $queryParts));
        
        $origin = $this->getOrigin($siteUrl);
        $baseHref = $this->getBaseHref($dom, $siteUrl);
        
        $icons = [];
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            if (!$href) continue;
            
            $abs = $this->resolveUrl($baseHref, $href);
            if (!$abs) continue;
            
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
        curl_close($ch);
        
        if ($ok && $code >= 200 && $code < 300) {
            $contentType = strtolower($contentType);
            if (strpos($contentType, 'image/') === 0 || 
                strpos($contentType, 'text/html') === false) {
                return true;
            }
        }
        
        // Some servers don't like HEAD; try GET quickly
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
        curl_close($ch);
        
        if (($resp !== false) && $code >= 200 && $code < 300) {
            $contentType = strtolower($contentType);
            if (strpos($contentType, 'image/') === 0 || 
                strpos($contentType, 'text/html') === false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * HTTP GET request
     */
    private function httpGet($url) {
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
        curl_close($ch);
        
        if ($res === false || $code < 200 || $code >= 400) return null;
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
        // Already absolute?
        if (preg_match('~^https?://~i', $rel)) return $rel;
        
        // Protocol-relative: //example.com/...
        if (strpos($rel, '//') === 0) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $rel;
        }
        
        $bp = parse_url($base);
        if (!$bp || empty($bp['scheme']) || empty($bp['host'])) return null;
        
        $scheme = $bp['scheme'];
        $host   = $bp['host'];
        $port   = isset($bp['port']) ? ':' . $bp['port'] : '';
        $path   = $bp['path'] ?? '/';
        
        // Root-relative
        if (strpos($rel, '/') === 0) {
            $path = $rel;
        } else {
            // Directory of base
            $dir = preg_replace('~/[^/]*$~', '/', $path);
            $path = $dir . $rel;
        }
        
        // Normalize ../ and ./
        $segments = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') array_pop($segments);
            else $segments[] = $seg;
        }
        $normPath = '/' . implode('/', $segments);
        
        return $scheme . '://' . $host . $port . $normPath;
    }
}
?> 