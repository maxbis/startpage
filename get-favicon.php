<?php
/**
 * Return the "best" favicon URL for a given site URL, or null if none found.
 */

// Configuration
$MAX_FAVICON_SIZE = 32; // Maximum favicon size in pixels (width/height)

echo "<pre>";

function pickFaviconUrl(string $siteUrl): ?string {
    $icons = discoverFavicons($siteUrl);

    // If we found explicit icons in HTML, try them (largest first)
    if (!empty($icons)) {
        usort($icons, 'compareIcons');
        foreach ($icons as $icon) {
            if (urlExists($icon['href'])) {
                return $icon['href'];
            }
        }
    }

    // Fallbacks in common locations
    $base = getOrigin($siteUrl);
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
        if (urlExists($url)) {
            return $url;
        }
    }

    return null;
}

/**
 * Discover icon candidates by parsing the HTML <link> tags.
 * Returns an array of ['href'=>absolute URL, 'rel'=>..., 'sizes'=>..., 'type'=>...]
 */
function discoverFavicons(string $siteUrl): array {
    $siteUrl = normalizeUrl($siteUrl);
    $html = httpGet($siteUrl);
    if ($html === null) return [];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) {
        return [];
    }
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    // All common rel values that can point to icons
    $rels = [
        'icon', 'shortcut icon', 'apple-touch-icon', 'apple-touch-icon-precomposed',
        'mask-icon', 'fluid-icon', 'alternate icon'
    ];
    $queryParts = [];
    foreach ($rels as $r) {
        // match rel tokens case-insensitively
        $queryParts[] = sprintf("//link[contains(translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '%s')]", strtolower($r));
    }
    $nodes = $xpath->query(implode(' | ', $queryParts));

    $origin = getOrigin($siteUrl);
    $baseHref = getBaseHref($dom, $origin);

    $icons = [];
    foreach ($nodes as $node) {
        /** @var DOMElement $node */
        $href = $node->getAttribute('href');
        $rel = $node->getAttribute('rel');
        $sizes = $node->getAttribute('sizes');
        $type = $node->getAttribute('type');
        
        if (!$href) continue;
        $abs = resolveUrl($baseHref, $href);
        if (!$abs) continue;
        $icons[] = [
            'href'  => $abs,
            'rel'   => strtolower(trim($rel)),
            'sizes' => strtolower(trim($sizes)), // e.g., "16x16", "32x32", "180x180", "any"
            'type'  => strtolower(trim($type)),  // e.g., "image/png", "image/svg+xml"
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
 * Compare icons by desirability: prefer SVG, then largest numeric size, then PNG/ICO.
 */
function compareIcons(array $a, array $b): int {
    $scoreA = iconScore($a);
    $scoreB = iconScore($b);
    // higher score first
    return $scoreB <=> $scoreA;
}
function iconScore(array $i): float {
    global $MAX_FAVICON_SIZE;
    
    // Prefer SVG (mask-icon or type svg)
    $isSvg = (strpos($i['type'] ?? '', 'svg') !== false) || (strpos($i['rel'] ?? '', 'mask-icon') !== false);
    $score = $isSvg ? 1000 : 0;

    // Parse sizes: may be like "16x16" or "32x32 48x48" or "any"
    $sizesStr = $i['sizes'] ?? '';
    $maxArea = 0;
    if ($sizesStr === 'any') {
        $maxArea = $MAX_FAVICON_SIZE * $MAX_FAVICON_SIZE; // treat as maximum size
    } else {
        foreach (preg_split('/\s+/', trim($sizesStr)) as $token) {
            if (preg_match('/^(\d+)x(\d+)$/', $token, $m)) {
                $width = (int)$m[1];
                $height = (int)$m[2];
                $area = $width * $height;
                
                // Only consider sizes up to the maximum
                if ($width <= $MAX_FAVICON_SIZE && $height <= $MAX_FAVICON_SIZE && $area > $maxArea) {
                    $maxArea = $area;
                }
            }
        }
    }
    // If no sizes given, give a modest base
    if ($maxArea === 0) $maxArea = 16 * 16; // Default to 16x16

    $score += $maxArea / 10.0;

    // Slight preference for PNG over ICO if sizes tie
    if (strpos($i['type'] ?? '', 'png') !== false) $score += 1.5;
    if (strpos($i['type'] ?? '', 'ico') !== false) $score += 1.0;

    return $score;
}

/**
 * Return true if URL responds 200-299 for a HEAD (or GET fallback).
 */
function urlExists(string $url): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY        => true,
        CURLOPT_FOLLOWLOCATION=> true,
        CURLOPT_MAXREDIRS     => 5,
        CURLOPT_TIMEOUT       => 10,
        CURLOPT_USERAGENT     => 'PHP Favicon Checker',
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_HEADER        => true,
    ]);
    $ok = curl_exec($ch) !== false;
    $code = $ok ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
    $contentType = $ok ? curl_getinfo($ch, CURLINFO_CONTENT_TYPE) : '';
    curl_close($ch);

    if ($ok && $code >= 200 && $code < 300) {
        // Check if content type is actually an image
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
        CURLOPT_TIMEOUT       => 10,
        CURLOPT_USERAGENT     => 'PHP Favicon Checker',
        CURLOPT_RETURNTRANSFER=> true,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (($resp !== false) && $code >= 200 && $code < 300) {
        // Check if content type is actually an image
        $contentType = strtolower($contentType);
        if (strpos($contentType, 'image/') === 0 || 
            strpos($contentType, 'text/html') === false) {
            return true;
        }
    }

    return false;
}

/** ----- Helpers ----- */

function httpGet(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION=> true,
        CURLOPT_MAXREDIRS     => 5,
        CURLOPT_CONNECTTIMEOUT=> 8,
        CURLOPT_TIMEOUT       => 12,
        CURLOPT_USERAGENT     => 'PHP Favicon Fetcher',
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_ENCODING      => '', // accept gzip/deflate
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $code < 200 || $code >= 400) return null;
    return $res;
}

function normalizeUrl(string $url): string {
    // If scheme missing, assume https
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    return $url;
}

function getOrigin(string $url): string {
    $p = parse_url(normalizeUrl($url));
    $scheme = $p['scheme'] ?? 'https';
    $host   = $p['host']   ?? '';
    $port   = isset($p['port']) ? ':' . $p['port'] : '';
    return strtolower($scheme . '://' . $host . $port);
}

function getBaseHref(DOMDocument $dom, string $origin): string {
    $bases = $dom->getElementsByTagName('base');
    if ($bases->length > 0) {
        $href = $bases->item(0)->getAttribute('href');
        if ($href) {
            $resolved = resolveUrl($origin, $href);
            if ($resolved) return $resolved;
        }
    }
    return $origin . '/';
}

/**
 * Resolve $rel against $base (RFC 3986-ish).
 */
function resolveUrl(string $base, string $rel): ?string {
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

$siteUrl = 'https://www.wijs.ovh/startpage';
$iconUrl = pickFaviconUrl($siteUrl);

if ($iconUrl) {
    // Make the page/tab itself use the discovered favicon:
    echo '<link rel="icon" href="' . htmlspecialchars($iconUrl, ENT_QUOTES) . '">';

    // Also render it on the page for the user to see:
    echo '<div style="margin-top:1rem">';
    echo '  <img src="' . htmlspecialchars($iconUrl, ENT_QUOTES) . '" alt="Favicon" style="width:64px;height:64px;image-rendering:auto">';
    echo '  <div style="font:14px/1.4 system-ui, sans-serif;margin-top:.5rem">';
    echo '    <strong>Favicon URL:</strong> ';
    echo '    <a href="' . htmlspecialchars($iconUrl, ENT_QUOTES) . '" target="_blank" rel="noopener">' . htmlspecialchars($iconUrl) . '</a>';
    echo '  </div>';
    echo '</div>';
} else {
    echo '<p style="color:#b00">No favicon found.</p>';
}