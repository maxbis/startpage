<?php
require_once '../includes/favicon/favicon-cache.php';
require_once '../includes/favicon/favicon-config.php';
require_once '../includes/favicon/favicon-discoverer.php';

function safeJsonEncode($value) {
    $json = json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );

    if ($json !== false) {
        return $json;
    }

    return json_encode([
        'encoding_error' => json_last_error_msg(),
        'stringified_value' => print_r($value, true),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{"encoding_error":"unknown"}';
}

function buildDebugBundle($url, $result, $error, $debugLog) {
    $summary = $result['debug_summary'] ?? [
        'total_steps' => count($debugLog),
        'steps' => [],
        'errors' => [],
        'success' => !empty($result),
        'final_result' => $result['favicon_url'] ?? null,
    ];

    return safeJsonEncode([
        'tested_at' => date('c'),
        'request_url' => $url,
        'php_version' => PHP_VERSION,
        'curl_extension' => extension_loaded('curl'),
        'result' => $result,
        'error' => $error,
        'debug_summary' => $summary,
        'debug_log' => $debugLog,
    ]);
}

function getResolutionMeta($result) {
    $source = $result['source'] ?? '';

    if ($source === 'external-fallback') {
        return [
            'label' => 'External Fallback',
            'classes' => 'wp-status-chip wp-status-chip--warning',
            'description' => 'The site blocked direct favicon fetching, so an external fallback service was used.',
        ];
    }

    if ($source === 'generated') {
        return [
            'label' => 'Generated Placeholder',
            'classes' => 'wp-status-chip wp-status-chip--danger',
            'description' => 'No usable favicon could be fetched, so a generated placeholder was returned.',
        ];
    }

    if (!empty($result['cached'])) {
        return [
            'label' => 'Cached Icon',
            'classes' => 'wp-status-chip wp-status-chip--success',
            'description' => 'A favicon was fetched successfully and cached locally.',
        ];
    }

    return [
        'label' => ucfirst($source ?: 'Unknown'),
        'classes' => 'wp-status-chip',
        'description' => 'Resolver returned a non-cached result.',
    ];
}

$url = '';
$result = null;
$error = null;
$debugBundle = null;
$debugLog = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = trim($_POST['url']);
    
    try {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }
        
        // Extract domain for display
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Use the shared resolver to find and cache the best favicon
        $faviconCache = new FaviconCache('../cache/favicons/', 86400 * 30, true, true);
        $resolved = $faviconCache->resolveForUrl($url, true);
        $faviconUrl = $resolved['favicon_url'];
        
        if ($faviconUrl) {
            $result = [
                'url' => $url,
                'domain' => $domain,
                'favicon_url' => $faviconUrl,
                'display_favicon_url' => FaviconConfig::getDisplayFaviconUrl($faviconUrl, $url, '../'),
                'source' => $resolved['source'],
                'source_url' => $resolved['source_url'],
                'timestamp' => date('Y-m-d H:i:s'),
                'debug_log' => $faviconCache->getDebugLog(),
                'debug_summary' => $faviconCache->getDebugSummary()
            ];
        } else {
            $error = 'No favicon found for this URL';
            
            // Add HTML content for debugging
            try {
                $testDiscoverer = new FaviconDiscoverer(32, 'StartPage Favicon Test', 10, true);
                $html = $testDiscoverer->httpGet($url);
                if ($html) {
                    $debugLog[] = [
                        'step' => 'debug_html',
                        'message' => 'Raw HTML content retrieved',
                        'data' => [
                            'html_length' => strlen($html),
                            'html_preview' => substr($html, 0, 1000) . '...',
                            'contains_link' => strpos($html, '<link') !== false,
                            'contains_favicon' => strpos(strtolower($html), 'favicon') !== false
                        ]
                    ];
                }
            } catch (Throwable $e) {
                $debugLog[] = [
                    'step' => 'debug_html',
                    'message' => 'Failed to retrieve HTML for debugging',
                    'data' => ['error' => $e->getMessage()]
                ];
            }
        }
        
    } catch (Throwable $e) {
        $error = 'Error: ' . $e->getMessage();
        $debugLog[] = [
            'step' => 'fatal',
            'message' => 'Request handling failed',
            'data' => [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ];
    }
}

if ($result || $error) {
    $debugBundle = buildDebugBundle($url, $result ?: [], $error, $result['debug_log'] ?? $debugLog ?? []);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favicon Discovery Test</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    <main class="wp-page-shell">
        <div class="wp-page-stack">
            <!-- Header -->
            <header class="wp-panel wp-panel--section">
                <div class="wp-page-header__row">
                    <h1 class="wp-page-title">🔍 Favicon Discovery Test</h1>
                    <a href="../app/" class="wp-button wp-button--secondary">
                        ← Back to Startpage
                    </a>
                </div>
                <p class="wp-page-lead">Test the favicon discovery functionality for any website</p>
            </header>

            <!-- Test Form -->
            <section class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">Test Favicon Discovery</h2>

                <form method="POST" class="wp-stack">
                    <div class="wp-field">
                        <label for="url" class="wp-label">
                            Website URL
                        </label>
                        <input 
                            type="url" 
                            id="url" 
                            name="url" 
                            value="<?= htmlspecialchars($url) ?>"
                            placeholder="https://example.com"
                            class="wp-input"
                            required
                        >
                        <p class="wp-help">Enter any website URL to test favicon discovery</p>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="wp-button wp-button--primary wp-button--block"
                    >
                        🔍 Discover Favicon
                    </button>
                </form>
            </div>

            <!-- Results -->
            <?php if ($result): ?>
                <?php $resolutionMeta = getResolutionMeta($result); ?>
                <section class="wp-panel wp-panel--section">
                    <h2 class="wp-section-title">✅ Discovery Results</h2>
                    <div class="wp-section-stack wp-section-stack--compact">
                        <span class="<?= htmlspecialchars($resolutionMeta['classes']) ?>">
                            <?= htmlspecialchars($resolutionMeta['label']) ?>
                        </span>
                        <p class="wp-supporting-text"><?= htmlspecialchars($resolutionMeta['description']) ?></p>
                    </div>
                    
                    <div class="wp-columns">
                        <!-- Favicon Display -->
                        <div class="wp-callout">
                            <h3 class="wp-subsection-title">Favicon Preview</h3>
                            <div class="wp-preview-row">
                                <img 
                                    src="<?= htmlspecialchars($result['display_favicon_url'] ?? $result['favicon_url']) ?>" 
                                    alt="Favicon" 
                                    class="wp-preview-image"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >
                                <div class="wp-load-error" hidden>❌ Failed to load</div>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="wp-detail-list">
                            <div>
                                <span class="wp-detail-label">Website:</span>
                                <a href="<?= htmlspecialchars($result['url']) ?>" target="_blank" class="wp-inline-link">
                                    <?= htmlspecialchars($result['domain']) ?>
                                </a>
                            </div>
                            <div>
                                <span class="wp-detail-label">Favicon URL:</span>
                                <div class="wp-data-block wp-data-block--single-line">
                                    <?= htmlspecialchars($result['favicon_url']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="wp-detail-label">Source Type:</span>
                                <span class="wp-supporting-text"><?= htmlspecialchars($result['source'] ?? 'unknown') ?></span>
                            </div>
                            <div>
                                <span class="wp-detail-label">Source URL:</span>
                                <div class="wp-data-block wp-data-block--single-line">
                                    <?= htmlspecialchars($result['source_url'] ?? 'n/a') ?>
                                </div>
                            </div>
                            <div>
                                <span class="wp-detail-label">Tested:</span>
                                <span class="wp-supporting-text"><?= htmlspecialchars($result['timestamp']) ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($debugBundle): ?>
                    <section class="wp-panel wp-panel--section">
                        <div class="wp-page-header__row">
                            <h2 class="wp-section-title">Copy Debug Bundle</h2>
                            <button
                                type="button"
                                id="copyDebugBundle"
                                class="wp-button wp-button--primary"
                            >
                                Copy JSON
                            </button>
                        </div>
                        <p class="wp-supporting-text">Copy this single JSON block and send it here.</p>
                        <textarea
                            id="debugBundle"
                            readonly
                            class="wp-textarea wp-textarea--debug"
                        ><?= htmlspecialchars($debugBundle) ?></textarea>
                    </section>
                <?php endif; ?>

                <!-- Debug Log -->
                <?php if (isset($result['debug_log']) && !empty($result['debug_log'])): ?>
                    <section class="wp-panel wp-panel--section">
                        <h2 class="wp-section-title">🔍 Debug Log</h2>
                        
                        <!-- Debug Summary -->
                        <?php $summary = $result['debug_summary'] ?? [
                            'total_steps' => count($result['debug_log']),
                            'steps' => [],
                            'errors' => [],
                            'success' => true,
                            'final_result' => $result['favicon_url']
                        ]; ?>
                        <div class="wp-callout wp-callout--info">
                            <h3 class="wp-subsection-title">📊 Debug Summary</h3>
                            <div class="wp-debug-summary">
                                <div>
                                    <strong>Total Steps:</strong>
                                    <span><?= $summary['total_steps'] ?></span>
                                </div>
                                <div>
                                    <strong>Outcome:</strong>
                                    <span><?= htmlspecialchars($resolutionMeta['label']) ?></span>
                                </div>
                                <div>
                                    <strong>Errors:</strong>
                                    <span><?= count($summary['errors']) ?></span>
                                </div>
                                <div>
                                    <strong>Steps:</strong>
                                    <span><?= implode(', ', array_unique($summary['steps'])) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($summary['errors'])): ?>
                                <div class="wp-danger-text">
                                    <strong>Errors Found:</strong>
                                    <ul class="wp-prose-list">
                                        <?php foreach ($summary['errors'] as $error): ?>
                                            <li>• <?= htmlspecialchars($error['step']) ?>: <?= htmlspecialchars($error['message']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="wp-debug-log">
                            <?php foreach ($result['debug_log'] as $index => $log): ?>
                                <div class="wp-debug-entry">
                                    <div class="wp-debug-entry__header">
                                        <span class="wp-debug-entry__step"><?= htmlspecialchars($log['step']) ?></span>
                                        <span class="wp-meta">
                                            <?php 
                                            $startTime = isset($result['debug_log'][0]['timestamp']) ? $result['debug_log'][0]['timestamp'] : 0;
                                            $currentTime = isset($log['timestamp']) ? $log['timestamp'] : 0;
                                            echo number_format(($currentTime - $startTime) * 1000, 2) . 'ms';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="wp-debug-entry__message"><?= htmlspecialchars($log['message']) ?></div>
                                    <?php if ($log['data']): ?>
                                        <div>
                                            <details>
                                                <summary>View Data</summary>
                                                <pre class="wp-data-block"><?= htmlspecialchars(safeJsonEncode($log['data'])) ?></pre>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Error Display -->
            <?php if ($error): ?>
                <div class="wp-alert wp-alert--error" role="alert">
                    <strong>Error:</strong> <?= htmlspecialchars(is_array($error) ? safeJsonEncode($error) : (string)$error) ?>
                </div>
                
                <!-- Debug Log for Errors -->
                <?php if (isset($debugLog) && !empty($debugLog)): ?>
                    <section class="wp-panel wp-panel--section">
                        <h2 class="wp-section-title">🔍 Debug Log (Error Case)</h2>
                        <div class="wp-debug-log">
                            <?php foreach ($debugLog as $index => $log): ?>
                                <div class="wp-debug-entry wp-debug-entry--danger">
                                    <div class="wp-debug-entry__header">
                                        <span class="wp-debug-entry__step"><?= htmlspecialchars($log['step']) ?></span>
                                        <span class="wp-meta">
                                            <?php 
                                            $startTime = isset($debugLog[0]['timestamp']) ? $debugLog[0]['timestamp'] : 0;
                                            $currentTime = isset($log['timestamp']) ? $log['timestamp'] : 0;
                                            echo number_format(($currentTime - $startTime) * 1000, 2) . 'ms';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="wp-debug-entry__message"><?= htmlspecialchars($log['message']) ?></div>
                                    <?php if ($log['data']): ?>
                                        <div>
                                            <details>
                                                <summary>View Data</summary>
                                                <pre class="wp-data-block"><?= htmlspecialchars(safeJsonEncode($log['data'])) ?></pre>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Test Examples -->
            <section class="wp-panel wp-panel--section">
                <h2 class="wp-section-title">🧪 Test Examples</h2>
                <p class="wp-supporting-text">Try these URLs to test different favicon scenarios:</p>

                <div class="wp-columns">
                    <div class="wp-callout wp-callout--info">
                        <h3 class="wp-subsection-title">Popular Sites</h3>
                        <ul class="wp-prose-list">
                            <li><a href="#" onclick="document.getElementById('url').value='https://github.com'; return false;" class="wp-inline-link">GitHub</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://stackoverflow.com'; return false;" class="wp-inline-link">Stack Overflow</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://news.ycombinator.com'; return false;" class="wp-inline-link">Hacker News</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://reddit.com'; return false;" class="wp-inline-link">Reddit</a></li>
                        </ul>
                    </div>
                    
                    <div class="wp-callout wp-callout--success">
                        <h3 class="wp-subsection-title">Tech Companies</h3>
                        <ul class="wp-prose-list">
                            <li><a href="#" onclick="document.getElementById('url').value='https://google.com'; return false;" class="wp-inline-link">Google</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://microsoft.com'; return false;" class="wp-inline-link">Microsoft</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://apple.com'; return false;" class="wp-inline-link">Apple</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://amazon.com'; return false;" class="wp-inline-link">Amazon</a></li>
                        </ul>
                    </div>
                    
                    <div class="wp-callout wp-callout--warning">
                        <h3 class="wp-subsection-title">Problematic Sites</h3>
                        <ul class="wp-prose-list">
                            <li><a href="#" onclick="document.getElementById('url').value='https://www.nu.nl'; return false;" class="wp-inline-link">NU.nl (0 link nodes)</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://nos.nl'; return false;" class="wp-inline-link">NOS.nl</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://tweakers.net'; return false;" class="wp-inline-link">Tweakers</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://localhost/startpage/app/'; return false;" class="wp-inline-link">Localhost Test</a></li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Debug Info -->
            <section class="wp-callout">
                <h3 class="wp-subsection-title">🔧 Debug Information</h3>
                <div class="wp-detail-list">
                    <div><strong>FaviconDiscoverer Class:</strong> <?= class_exists('FaviconDiscoverer') ? '✅ Loaded' : '❌ Not found' ?></div>
                    <div><strong>Test URL:</strong> <?= htmlspecialchars($url ?: 'None') ?></div>
                    <div><strong>PHP Version:</strong> <?= PHP_VERSION ?></div>
                    <div><strong>cURL Extension:</strong> <?= extension_loaded('curl') ? '✅ Available' : '❌ Not available' ?></div>
                </div>
            </section>
        </div>
    </main>
    <script>
        const debugBundle = document.getElementById('debugBundle');
        const copyDebugBundle = document.getElementById('copyDebugBundle');

        copyDebugBundle?.addEventListener('click', async () => {
            if (!debugBundle) {
                return;
            }

            debugBundle.select();
            debugBundle.setSelectionRange(0, debugBundle.value.length);

            try {
                await navigator.clipboard.writeText(debugBundle.value);
                copyDebugBundle.textContent = 'Copied';
                setTimeout(() => {
                    copyDebugBundle.textContent = 'Copy JSON';
                }, 1500);
            } catch (error) {
                copyDebugBundle.textContent = 'Press Cmd/Ctrl+C';
                setTimeout(() => {
                    copyDebugBundle.textContent = 'Copy JSON';
                }, 2000);
            }
        });
    </script>
</body>
</html> 
