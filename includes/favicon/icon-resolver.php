<?php
/**
 * Icon Resolver
 * Resolves, validates, caches, and serves bookmark icons for websites.
 */

require_once __DIR__ . '/favicon-config.php';

class IconResolver {
    private const BROWSER_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36';
    private const ROOT_ICON_PATHS = [
        '/favicon.ico',
        '/favicon.svg',
        '/favicon.png',
        '/apple-touch-icon.png',
        '/apple-touch-icon-precomposed.png',
        '/favicon-32x32.png',
        '/favicon-16x16.png',
    ];

    private const MANIFEST_PROBES = [
        '/site.webmanifest',
        '/manifest.webmanifest',
    ];

    private const CACHE_EXTENSIONS = ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];

    private $cacheDir;
    private $cacheTime;
    private $userAgent;
    private $timeout;
    private $debug;
    private $debugLog = [];

    public function __construct(
        $cacheDir = null,
        $cacheTime = 86400 * 30,
        $userAgent = self::BROWSER_USER_AGENT,
        $timeout = 10,
        $debug = false
    ) {
        $this->cacheDir = $cacheDir ?: dirname(__DIR__, 2) . '/cache/favicons/';
        $this->cacheTime = $cacheTime;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
        $this->debug = $debug;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getDebugLog() {
        return $this->debugLog;
    }

    public function clearDebugLog() {
        $this->debugLog = [];
    }

    public function getDebugSummary() {
        $summary = [
            'total_steps' => count($this->debugLog),
            'steps' => [],
            'errors' => [],
            'success' => false,
            'final_result' => null,
        ];

        foreach ($this->debugLog as $log) {
            $summary['steps'][] = $log['step'];
            $message = strtolower($log['message'] ?? '');

            if (strpos($message, 'failed') !== false || strpos($message, 'error') !== false) {
                $summary['errors'][] = [
                    'step' => $log['step'],
                    'message' => $log['message'],
                    'data' => $log['data'] ?? null,
                ];
            }

            if (
                strpos($message, 'resolved icon') !== false
                || strpos($message, 'using generated placeholder') !== false
                || strpos($message, 'using external favicon fallback') !== false
            ) {
                $summary['success'] = true;
                $summary['final_result'] = $log['data']['favicon_url'] ?? null;
            }
        }

        return $summary;
    }

    public function exportDebugLog() {
        return json_encode($this->debugLog, JSON_PRETTY_PRINT);
    }

    public function resolveForUrl($bookmarkUrl, $forceRefresh = false) {
        $this->clearDebugLog();

        $normalizedUrl = $this->normalizeUrl($bookmarkUrl);
        $cacheBaseName = $this->getCacheBaseName($normalizedUrl);

        $this->addDebugLog('resolve', 'Starting icon resolution', [
            'bookmark_url' => $bookmarkUrl,
            'normalized_url' => $normalizedUrl,
            'force_refresh' => $forceRefresh,
            'cache_base_name' => $cacheBaseName,
        ]);

        if (!$forceRefresh) {
            $existingCache = $this->findExistingCacheFile($cacheBaseName);
            if ($existingCache && (time() - filemtime($existingCache['path'])) < $this->cacheTime) {
                $this->addDebugLog('cache', 'Using fresh cached icon', [
                    'favicon_url' => $existingCache['url'],
                    'cache_path' => $existingCache['path'],
                ]);

                return $this->buildResult([
                    'normalized_url' => $normalizedUrl,
                    'final_url' => $normalizedUrl,
                    'source_url' => null,
                    'favicon_url' => $existingCache['url'],
                    'source' => 'cache',
                    'cached' => true,
                    'failure_reason' => null,
                ]);
            }
        } else {
            $this->deleteExistingCacheFiles($cacheBaseName);
        }

        $pageResponse = $this->fetchUrl($normalizedUrl);
        $finalUrl = $pageResponse['final_url'] ?? $normalizedUrl;
        $pageOrigin = $this->getOrigin($finalUrl ?: $normalizedUrl);
        $this->addDebugLog('fetch', 'Fetched bookmark page', $this->summarizeResponse($pageResponse));

        $candidates = [];
        if ($this->isHtmlResponse($pageResponse)) {
            $pageDiscovery = $this->discoverFromHtml($pageResponse['body'], $finalUrl, 'html');
            $candidates = array_merge($candidates, $pageDiscovery['icons']);
            $this->addDebugLog('discover', 'Discovered page icons from HTML', [
                'page_url' => $finalUrl,
                'icon_count' => count($pageDiscovery['icons']),
                'manifest_count' => count($pageDiscovery['manifests']),
            ]);

            foreach ($pageDiscovery['manifests'] as $manifestUrl) {
                $candidates = array_merge($candidates, $this->discoverFromManifest($manifestUrl));
            }
        } else {
            $this->addDebugLog('discover', 'Bookmark page was not recognized as HTML', [
                'page_url' => $finalUrl,
                'content_type' => $pageResponse['content_type'] ?? '',
                'status' => $pageResponse['status'] ?? 0,
            ]);
        }

        foreach (self::MANIFEST_PROBES as $manifestPath) {
            $candidates = array_merge($candidates, $this->discoverFromManifest(rtrim($pageOrigin, '/') . $manifestPath));
        }

        foreach (self::ROOT_ICON_PATHS as $iconPath) {
            $candidates[] = $this->buildCandidate([
                'href' => rtrim($pageOrigin, '/') . $iconPath,
                'type' => '',
                'sizes' => '',
                'rel' => 'root-probe',
                'source' => 'root-probe',
                'context' => 'page',
                'base_url' => $pageOrigin,
            ]);
        }

        if ($this->shouldRetryHomepage($finalUrl)) {
            $homepageUrl = rtrim($pageOrigin, '/') . '/';
            $homeResponse = $this->fetchUrl($homepageUrl);
            $this->addDebugLog('fetch', 'Fetched homepage fallback', $this->summarizeResponse($homeResponse));
            if ($this->isHtmlResponse($homeResponse)) {
                $homeDiscovery = $this->discoverFromHtml($homeResponse['body'], $homeResponse['final_url'] ?? $homepageUrl, 'html');
                $this->addDebugLog('discover', 'Discovered homepage icons from HTML', [
                    'page_url' => $homeResponse['final_url'] ?? $homepageUrl,
                    'icon_count' => count($homeDiscovery['icons']),
                    'manifest_count' => count($homeDiscovery['manifests']),
                ]);
                foreach ($homeDiscovery['icons'] as $icon) {
                    $icon['context'] = 'homepage';
                    $candidates[] = $icon;
                }
                foreach ($homeDiscovery['manifests'] as $manifestUrl) {
                    foreach ($this->discoverFromManifest($manifestUrl) as $icon) {
                        $icon['context'] = 'homepage';
                        $candidates[] = $icon;
                    }
                }
            }
        }

        $candidates = $this->deduplicateCandidates($candidates);
        $this->addDebugLog('resolve', 'Collected icon candidates', [
            'count' => count($candidates),
            'sample' => array_slice(array_map(function ($candidate) {
                return [
                    'href' => $candidate['href'],
                    'source' => $candidate['source'],
                    'rel' => $candidate['rel'],
                    'sizes' => $candidate['sizes'],
                ];
            }, $candidates), 0, 10),
        ]);

        $best = $this->resolveBestCandidate($candidates, $pageOrigin);
        if ($best) {
            $cachedUrl = $this->storeCachedIcon($cacheBaseName, $best['response']['body'], $best['response'], $best['candidate']);

            $result = $this->buildResult([
                'normalized_url' => $normalizedUrl,
                'final_url' => $finalUrl,
                'source_url' => $best['response']['final_url'] ?: $best['candidate']['href'],
                'favicon_url' => $cachedUrl,
                'source' => $best['candidate']['source'],
                'cached' => true,
                'failure_reason' => null,
            ]);

            $this->addDebugLog('resolve', 'Resolved icon candidate', $result);
            return $result;
        }

        $failureReason = 'No valid site icon found';
        $externalFallback = FaviconConfig::getExternalFallbackFaviconUrl($normalizedUrl);
        if ($externalFallback !== '') {
            $result = $this->buildResult([
                'normalized_url' => $normalizedUrl,
                'final_url' => $finalUrl,
                'source_url' => $externalFallback,
                'favicon_url' => $externalFallback,
                'source' => 'external-fallback',
                'cached' => false,
                'failure_reason' => $failureReason,
            ]);

            $this->addDebugLog('resolve', 'Using external favicon fallback', $result);
            return $result;
        }

        $generated = FaviconConfig::getGeneratedFaviconDataUri($normalizedUrl);
        $result = $this->buildResult([
            'normalized_url' => $normalizedUrl,
            'final_url' => $finalUrl,
            'source_url' => null,
            'favicon_url' => $generated,
            'source' => 'generated',
            'cached' => false,
            'failure_reason' => $failureReason,
        ]);

        $this->addDebugLog('resolve', 'Using generated placeholder', $result);
        return $result;
    }

    public function cleanupCache() {
        foreach ($this->getAllCacheFiles() as $file) {
            if ((time() - filemtime($file)) > $this->cacheTime) {
                unlink($file);
            }
        }
    }

    public function clearCache() {
        $deleted = 0;
        foreach ($this->getAllCacheFiles() as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function getCacheStats() {
        $files = $this->getAllCacheFiles();
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
        }

        return [
            'count' => count($files),
            'size' => $totalSize,
            'size_formatted' => $this->formatBytes($totalSize),
        ];
    }

    public function getCachePreviewFiles() {
        return $this->getAllCacheFiles();
    }

    private function buildResult(array $data) {
        return [
            'normalized_url' => $data['normalized_url'] ?? '',
            'final_url' => $data['final_url'] ?? '',
            'source_url' => $data['source_url'] ?? null,
            'favicon_url' => $data['favicon_url'] ?? '',
            'source' => $data['source'] ?? 'generated',
            'cached' => (bool)($data['cached'] ?? false),
            'failure_reason' => $data['failure_reason'] ?? null,
        ];
    }

    private function addDebugLog($step, $message, $data = null) {
        if (!$this->debug) {
            return;
        }

        $this->debugLog[] = [
            'step' => $step,
            'message' => $message,
            'data' => $data,
            'timestamp' => microtime(true),
        ];
    }

    private function buildCandidate(array $candidate) {
        return [
            'href' => $candidate['href'],
            'type' => strtolower(trim($candidate['type'] ?? '')),
            'sizes' => strtolower(trim($candidate['sizes'] ?? '')),
            'rel' => strtolower(trim($candidate['rel'] ?? '')),
            'source' => $candidate['source'] ?? 'html',
            'context' => $candidate['context'] ?? 'page',
            'base_url' => $candidate['base_url'] ?? '',
        ];
    }

    private function resolveBestCandidate(array $candidates, $pageOrigin) {
        usort($candidates, function ($a, $b) use ($pageOrigin) {
            return $this->estimateCandidateScore($b, $pageOrigin) <=> $this->estimateCandidateScore($a, $pageOrigin);
        });

        $best = null;
        foreach ($candidates as $candidate) {
            $response = $this->fetchUrl($candidate['href']);
            $isImage = $this->isImageResponse($response);
            $this->addDebugLog('candidate', $isImage ? 'Candidate returned image response' : 'Candidate rejected after fetch', [
                'href' => $candidate['href'],
                'source' => $candidate['source'],
                'rel' => $candidate['rel'],
                'sizes' => $candidate['sizes'],
                'estimated_score' => $this->estimateCandidateScore($candidate, $pageOrigin),
                'response' => $this->summarizeResponse($response),
            ]);

            if (!$isImage) {
                continue;
            }

            $score = $this->scoreResolvedCandidate($candidate, $response, $pageOrigin);
            $this->addDebugLog('candidate', 'Candidate scored', [
                'href' => $candidate['href'],
                'score' => $score,
                'content_type' => $response['content_type'] ?: $candidate['type'],
                'final_url' => $response['final_url'] ?: $candidate['href'],
            ]);
            if (!$best || $score > $best['score']) {
                $best = [
                    'candidate' => $candidate,
                    'response' => $response,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    private function estimateCandidateScore(array $candidate, $pageOrigin) {
        $score = 0;

        if ($this->getOrigin($candidate['href']) === $pageOrigin) {
            $score += 30;
        }

        $score += $this->sourcePreferenceScore($candidate);
        $score += $this->pathPreferenceScore($candidate['href']);

        if ($candidate['context'] === 'page') {
            $score += 6;
        }

        $score += $this->formatScore($candidate['type']);
        $score += $this->sizeScore($candidate['sizes']);

        return $score;
    }

    private function scoreResolvedCandidate(array $candidate, array $response, $pageOrigin) {
        $score = 100;
        $score += ($this->getOrigin($response['final_url'] ?: $candidate['href']) === $pageOrigin) ? 30 : 0;
        $score += $this->formatScore(($response['content_type'] ?: $candidate['type']));
        $score += $this->sizeScore($candidate['sizes']);
        $score += $this->sourcePreferenceScore($candidate);
        $score += $this->pathPreferenceScore($response['final_url'] ?: $candidate['href']);

        if ($candidate['context'] === 'page') {
            $score += 5;
        }

        return $score;
    }

    /**
     * Prefer browser-tab favicon sources over app-tile sources.
     * HTML rel=icon and root favicon files should beat manifest icons when both exist.
     */
    private function sourcePreferenceScore(array $candidate) {
        if (($candidate['source'] ?? '') === 'html') {
            $rel = strtolower($candidate['rel'] ?? '');
            if (strpos($rel, 'icon') !== false || strpos($rel, 'shortcut icon') !== false) {
                return 40;
            }
            return 28;
        }

        if (($candidate['source'] ?? '') === 'root-probe') {
            return 34;
        }

        if (($candidate['source'] ?? '') === 'manifest') {
            return 4;
        }

        return 0;
    }

    /**
     * Give explicit preference to classic favicon paths used by browser tabs.
     */
    private function pathPreferenceScore($url) {
        $path = strtolower((string)(parse_url($url, PHP_URL_PATH) ?? ''));

        if ($path === '/favicon.ico') {
            return 34;
        }
        if ($path === '/favicon.png' || $path === '/favicon.svg') {
            return 28;
        }
        if ($path === '/favicon-32x32.png' || $path === '/favicon-16x16.png') {
            return 22;
        }
        if ($path === '/apple-touch-icon.png' || $path === '/apple-touch-icon-precomposed.png') {
            return 8;
        }

        return 0;
    }

    private function formatScore($type) {
        $type = strtolower((string)$type);

        if (strpos($type, 'svg') !== false) {
            return 24;
        }
        if (strpos($type, 'png') !== false) {
            return 20;
        }
        if (strpos($type, 'webp') !== false) {
            return 16;
        }
        if (strpos($type, 'icon') !== false || strpos($type, 'ico') !== false) {
            return 14;
        }
        if (strpos($type, 'jpg') !== false || strpos($type, 'jpeg') !== false) {
            return 10;
        }
        if (strpos($type, 'gif') !== false) {
            return 6;
        }

        return 4;
    }

    private function sizeScore($sizes) {
        $sizes = strtolower(trim((string)$sizes));
        if ($sizes === 'any') {
            return 26;
        }

        $best = 8;
        foreach (preg_split('/\s+/', $sizes) as $token) {
            if (!preg_match('/^(\d+)x(\d+)$/', $token, $matches)) {
                continue;
            }

            $width = (int)$matches[1];
            $height = (int)$matches[2];
            $isSquare = ($width === $height);
            $largest = max($width, $height);

            $score = $isSquare ? 12 : 4;
            if ($largest >= 32 && $largest <= 192) {
                $score += 18 + (int)round(min($largest, 96) / 24);
            } elseif ($largest > 192) {
                $score += 16;
            } elseif ($largest >= 24) {
                $score += 12;
            } elseif ($largest >= 16) {
                $score += 8;
            }

            if ($score > $best) {
                $best = $score;
            }
        }

        return $best;
    }

    private function discoverFromHtml($html, $pageUrl, $source) {
        $icons = [];
        $manifests = [];

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            libxml_clear_errors();
            return ['icons' => [], 'manifests' => []];
        }
        libxml_clear_errors();

        $baseUrl = $this->getBaseHref($dom, $pageUrl);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//link[@href]');

        foreach ($nodes as $node) {
            $rel = strtolower(trim($node->getAttribute('rel')));
            $href = trim($node->getAttribute('href'));
            if ($href === '') {
                continue;
            }

            $absoluteHref = $this->resolveUrl($baseUrl, $href);
            if (!$absoluteHref) {
                continue;
            }

            if (strpos($rel, 'manifest') !== false) {
                $manifests[] = $absoluteHref;
            }

            if (strpos($rel, 'icon') === false && strpos($rel, 'shortcut icon') === false && strpos($rel, 'mask-icon') === false) {
                continue;
            }

            $icons[] = $this->buildCandidate([
                'href' => $absoluteHref,
                'type' => $node->getAttribute('type'),
                'sizes' => $node->getAttribute('sizes'),
                'rel' => $rel,
                'source' => $source,
                'context' => 'page',
                'base_url' => $baseUrl,
            ]);
        }

        return [
            'icons' => $this->deduplicateCandidates($icons),
            'manifests' => array_values(array_unique($manifests)),
        ];
    }

    private function discoverFromManifest($manifestUrl) {
        $response = $this->fetchUrl($manifestUrl);
        $this->addDebugLog('manifest', 'Fetched manifest candidate', [
            'manifest_url' => $manifestUrl,
            'response' => $this->summarizeResponse($response),
        ]);
        if (!$response['ok']) {
            return [];
        }

        $manifest = json_decode($response['body'], true);
        if (!is_array($manifest) || empty($manifest['icons']) || !is_array($manifest['icons'])) {
            $this->addDebugLog('manifest', 'Manifest response did not contain icons', [
                'manifest_url' => $response['final_url'] ?: $manifestUrl,
            ]);
            return [];
        }

        $icons = [];
        foreach ($manifest['icons'] as $icon) {
            if (empty($icon['src'])) {
                continue;
            }

            $resolved = $this->resolveUrl($response['final_url'] ?: $manifestUrl, $icon['src']);
            if (!$resolved) {
                continue;
            }

            $icons[] = $this->buildCandidate([
                'href' => $resolved,
                'type' => $icon['type'] ?? '',
                'sizes' => $icon['sizes'] ?? '',
                'rel' => $icon['purpose'] ?? 'manifest',
                'source' => 'manifest',
                'context' => 'page',
                'base_url' => $response['final_url'] ?: $manifestUrl,
            ]);
        }

        $icons = $this->deduplicateCandidates($icons);
        $this->addDebugLog('manifest', 'Discovered icons from manifest', [
            'manifest_url' => $response['final_url'] ?: $manifestUrl,
            'icon_count' => count($icons),
        ]);

        return $icons;
    }

    private function deduplicateCandidates(array $candidates) {
        $unique = [];
        $deduped = [];

        foreach ($candidates as $candidate) {
            $key = $candidate['href'];
            if (isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $deduped[] = $candidate;
        }

        return $deduped;
    }

    private function shouldRetryHomepage($url) {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';

        return $path !== '/' || $query !== '';
    }

    private function fetchUrl($url) {
        $this->addDebugLog('fetch', 'Fetching URL', ['url' => $url]);

        $origin = $this->getOrigin($url);
        $referer = $origin ? rtrim($origin, '/') . '/' : $url;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,nl;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_AUTOREFERER => true,
            CURLOPT_REFERER => $referer,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        $response = [
            'ok' => $body !== false,
            'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '',
            'final_url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url,
            'body' => $body !== false ? $body : '',
            'error' => curl_error($ch),
        ];
        curl_close($ch);

        if (!$response['ok'] || $response['status'] < 200 || $response['status'] >= 400) {
            $response['ok'] = false;
        }

        return $response;
    }

    private function summarizeResponse(array $response) {
        return [
            'ok' => (bool)($response['ok'] ?? false),
            'status' => (int)($response['status'] ?? 0),
            'content_type' => (string)($response['content_type'] ?? ''),
            'final_url' => (string)($response['final_url'] ?? ''),
            'error' => (string)($response['error'] ?? ''),
            'body_length' => strlen((string)($response['body'] ?? '')),
        ];
    }

    private function isHtmlResponse(array $response) {
        if (!$response['ok']) {
            return false;
        }

        $contentType = strtolower($response['content_type']);
        return strpos($contentType, 'text/html') !== false || stripos(substr($response['body'], 0, 200), '<!doctype html') !== false || stripos(substr($response['body'], 0, 200), '<html') !== false;
    }

    private function isImageResponse(array $response) {
        if (!$response['ok'] || $response['body'] === '') {
            return false;
        }

        $contentType = strtolower($response['content_type']);
        if (strpos($contentType, 'image/') === 0) {
            return true;
        }

        if (strpos($contentType, 'svg') !== false || strpos($contentType, 'xml') !== false) {
            return stripos($response['body'], '<svg') !== false;
        }

        return false;
    }

    private function storeCachedIcon($cacheBaseName, $body, array $response, array $candidate) {
        $extension = $this->detectExtension($response, $candidate);
        $filename = $cacheBaseName . '.' . $extension;
        $cachePath = rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        $this->deleteExistingCacheFiles($cacheBaseName);
        file_put_contents($cachePath, $body);

        return 'cache/favicons/' . $filename;
    }

    private function detectExtension(array $response, array $candidate) {
        $type = strtolower($response['content_type'] ?: $candidate['type']);
        if (strpos($type, 'svg') !== false) {
            return 'svg';
        }
        if (strpos($type, 'png') !== false) {
            return 'png';
        }
        if (strpos($type, 'webp') !== false) {
            return 'webp';
        }
        if (strpos($type, 'jpeg') !== false || strpos($type, 'jpg') !== false) {
            return 'jpg';
        }
        if (strpos($type, 'gif') !== false) {
            return 'gif';
        }
        if (strpos($type, 'icon') !== false || strpos($type, 'ico') !== false) {
            return 'ico';
        }

        $path = parse_url($response['final_url'] ?: $candidate['href'], PHP_URL_PATH);
        $extension = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));
        if (in_array($extension, self::CACHE_EXTENSIONS, true)) {
            return $extension;
        }

        return 'ico';
    }

    private function getAllCacheFiles() {
        $files = [];
        foreach (self::CACHE_EXTENSIONS as $extension) {
            $files = array_merge($files, glob(rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . '*.' . $extension));
        }
        return $files;
    }

    private function getCacheBaseName($bookmarkUrl) {
        $parsed = parse_url($bookmarkUrl);
        $host = preg_replace('/[^a-zA-Z0-9.-]/', '_', strtolower($parsed['host'] ?? 'site'));
        $path = $parsed['path'] ?? '/';
        $cacheKey = strtolower(($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $path);
        return $host . '-' . substr(sha1($cacheKey), 0, 12);
    }

    private function findExistingCacheFile($cacheBaseName) {
        foreach (self::CACHE_EXTENSIONS as $extension) {
            $path = rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $cacheBaseName . '.' . $extension;
            if (file_exists($path)) {
                return [
                    'path' => $path,
                    'url' => 'cache/favicons/' . basename($path),
                ];
            }
        }

        return null;
    }

    private function deleteExistingCacheFiles($cacheBaseName) {
        foreach (self::CACHE_EXTENSIONS as $extension) {
            $path = rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $cacheBaseName . '.' . $extension;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function normalizeUrl($url) {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        $path = $path === '' ? '/' : $path;
        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

        return $scheme . '://' . $host . $port . $path . $query;
    }

    private function getOrigin($url) {
        $parsed = parse_url($this->normalizeUrl($url));
        if (!$parsed || empty($parsed['host'])) {
            return '';
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        return $scheme . '://' . $host . $port;
    }

    private function getBaseHref(DOMDocument $dom, $pageUrl) {
        $bases = $dom->getElementsByTagName('base');
        if ($bases->length > 0) {
            $href = trim($bases->item(0)->getAttribute('href'));
            if ($href !== '') {
                $resolved = $this->resolveUrl($pageUrl, $href);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        return $pageUrl;
    }

    private function resolveUrl($base, $relative) {
        $relative = trim((string)$relative);
        if ($relative === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $relative)) {
            return $relative;
        }

        if (strpos($relative, '//') === 0) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $relative;
        }

        $baseParts = parse_url($base);
        if (!$baseParts || empty($baseParts['host'])) {
            return null;
        }

        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $path = $baseParts['path'] ?? '/';

        if (strpos($relative, '/') === 0) {
            $path = $relative;
        } else {
            $directory = preg_replace('~/[^/]*$~', '/', $path);
            $path = $directory . $relative;
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return $scheme . '://' . $host . $port . '/' . implode('/', $segments);
    }

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
