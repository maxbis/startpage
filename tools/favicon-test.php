<?php
require_once '../includes/favicon/favicon-discoverer.php';

$url = '';
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = trim($_POST['url']);
    
    try {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }
        
        // Extract domain for display
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Use FaviconDiscoverer to find the best favicon
        $discoverer = new FaviconDiscoverer(32, 'StartPage Favicon Test', 10, true);
        $faviconUrl = $discoverer->getFaviconUrl($url);
        
        if ($faviconUrl) {
            $result = [
                'url' => $url,
                'domain' => $domain,
                'favicon_url' => $faviconUrl,
                'timestamp' => date('Y-m-d H:i:s'),
                'debug_log' => $discoverer->getDebugLog()
            ];
        } else {
            $error = 'No favicon found for this URL';
            $debugLog = $discoverer->getDebugLog();
            
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
            } catch (Exception $e) {
                $debugLog[] = [
                    'step' => 'debug_html',
                    'message' => 'Failed to retrieve HTML for debugging',
                    'data' => ['error' => $e->getMessage()]
                ];
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-800">🔍 Favicon Discovery Test</h1>
                    <a href="../app/" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        ← Back to Startpage
                    </a>
                </div>
                <p class="text-gray-600 mt-2">Test the favicon discovery functionality for any website</p>
            </div>

            <!-- Test Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Test Favicon Discovery</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                            Website URL
                        </label>
                        <input 
                            type="url" 
                            id="url" 
                            name="url" 
                            value="<?= htmlspecialchars($url) ?>"
                            placeholder="https://example.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Enter any website URL to test favicon discovery</p>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-6 rounded-lg transition-colors"
                    >
                        🔍 Discover Favicon
                    </button>
                </form>
            </div>

            <!-- Results -->
            <?php if ($result): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">✅ Discovery Results</h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Favicon Display -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-700 mb-3">Favicon Preview</h3>
                            <div class="flex items-center space-x-4">
                                <img 
                                    src="<?= htmlspecialchars($result['favicon_url']) ?>" 
                                    alt="Favicon" 
                                    class="w-16 h-16 border border-gray-300 rounded"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >
                                <div class="hidden text-red-500 text-sm">❌ Failed to load</div>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="space-y-3">
                            <div>
                                <span class="font-semibold text-gray-700">Website:</span>
                                <a href="<?= htmlspecialchars($result['url']) ?>" target="_blank" class="text-blue-600 hover:underline ml-2">
                                    <?= htmlspecialchars($result['domain']) ?>
                                </a>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Favicon URL:</span>
                                <div class="mt-1 p-2 bg-gray-100 rounded text-sm font-mono break-all">
                                    <?= htmlspecialchars($result['favicon_url']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Tested:</span>
                                <span class="text-gray-600 ml-2"><?= htmlspecialchars($result['timestamp']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Debug Log -->
                <?php if (isset($result['debug_log']) && !empty($result['debug_log'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">🔍 Debug Log</h2>
                        
                        <!-- Debug Summary -->
                        <?php 
                        $summary = $discoverer->getDebugSummary();
                        ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h3 class="font-semibold text-blue-800 mb-2">📊 Debug Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="font-semibold">Total Steps:</span>
                                    <span class="ml-2"><?= $summary['total_steps'] ?></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Success:</span>
                                    <span class="ml-2"><?= $summary['success'] ? '✅ Yes' : '❌ No' ?></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Errors:</span>
                                    <span class="ml-2"><?= count($summary['errors']) ?></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Steps:</span>
                                    <span class="ml-2"><?= implode(', ', array_unique($summary['steps'])) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($summary['errors'])): ?>
                                <div class="mt-3">
                                    <span class="font-semibold text-red-700">Errors Found:</span>
                                    <ul class="mt-1 text-xs text-red-600">
                                        <?php foreach ($summary['errors'] as $error): ?>
                                            <li>• <?= htmlspecialchars($error['step']) ?>: <?= htmlspecialchars($error['message']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <?php foreach ($result['debug_log'] as $index => $log): ?>
                                <div class="mb-3 p-3 bg-white rounded border-l-4 border-blue-500">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-blue-700"><?= htmlspecialchars($log['step']) ?></span>
                                        <span class="text-xs text-gray-500">
                                            <?php 
                                            $startTime = isset($result['debug_log'][0]['timestamp']) ? $result['debug_log'][0]['timestamp'] : 0;
                                            $currentTime = isset($log['timestamp']) ? $log['timestamp'] : 0;
                                            echo number_format(($currentTime - $startTime) * 1000, 2) . 'ms';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($log['message']) ?></div>
                                    <?php if ($log['data']): ?>
                                        <div class="text-xs text-gray-600">
                                            <details>
                                                <summary class="cursor-pointer hover:text-gray-800">View Data</summary>
                                                <pre class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($log['data'], JSON_PRETTY_PRINT)) ?></pre>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Error Display -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
                
                <!-- Debug Log for Errors -->
                <?php if (isset($debugLog) && !empty($debugLog)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">🔍 Debug Log (Error Case)</h2>
                        <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <?php foreach ($debugLog as $index => $log): ?>
                                <div class="mb-3 p-3 bg-white rounded border-l-4 border-red-500">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-red-700"><?= htmlspecialchars($log['step']) ?></span>
                                        <span class="text-xs text-gray-500">
                                            <?php 
                                            $startTime = isset($debugLog[0]['timestamp']) ? $debugLog[0]['timestamp'] : 0;
                                            $currentTime = isset($log['timestamp']) ? $log['timestamp'] : 0;
                                            echo number_format(($currentTime - $startTime) * 1000, 2) . 'ms';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($log['message']) ?></div>
                                    <?php if ($log['data']): ?>
                                        <div class="text-xs text-gray-600">
                                            <details>
                                                <summary class="cursor-pointer hover:text-gray-800">View Data</summary>
                                                <pre class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($log['data'], JSON_PRETTY_PRINT)) ?></pre>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Test Examples -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">🧪 Test Examples</h2>
                <p class="text-gray-600 mb-4">Try these URLs to test different favicon scenarios:</p>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">Popular Sites</h3>
                        <ul class="space-y-1 text-sm">
                            <li><a href="#" onclick="document.getElementById('url').value='https://github.com'; return false;" class="text-blue-600 hover:underline">GitHub</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://stackoverflow.com'; return false;" class="text-blue-600 hover:underline">Stack Overflow</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://news.ycombinator.com'; return false;" class="text-blue-600 hover:underline">Hacker News</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://reddit.com'; return false;" class="text-blue-600 hover:underline">Reddit</a></li>
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-2">Tech Companies</h3>
                        <ul class="space-y-1 text-sm">
                            <li><a href="#" onclick="document.getElementById('url').value='https://google.com'; return false;" class="text-green-600 hover:underline">Google</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://microsoft.com'; return false;" class="text-green-600 hover:underline">Microsoft</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://apple.com'; return false;" class="text-green-600 hover:underline">Apple</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://amazon.com'; return false;" class="text-green-600 hover:underline">Amazon</a></li>
                        </ul>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">Problematic Sites</h3>
                        <ul class="space-y-1 text-sm">
                            <li><a href="#" onclick="document.getElementById('url').value='https://www.nu.nl'; return false;" class="text-yellow-600 hover:underline">NU.nl (0 link nodes)</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://nos.nl'; return false;" class="text-yellow-600 hover:underline">NOS.nl</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://tweakers.net'; return false;" class="text-yellow-600 hover:underline">Tweakers</a></li>
                            <li><a href="#" onclick="document.getElementById('url').value='https://localhost/startpage/app/'; return false;" class="text-yellow-600 hover:underline">Localhost Test</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="bg-gray-50 rounded-lg p-4 mt-6">
                <h3 class="font-semibold text-gray-700 mb-2">🔧 Debug Information</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <div><strong>FaviconDiscoverer Class:</strong> <?= class_exists('FaviconDiscoverer') ? '✅ Loaded' : '❌ Not found' ?></div>
                    <div><strong>Test URL:</strong> <?= htmlspecialchars($url ?: 'None') ?></div>
                    <div><strong>PHP Version:</strong> <?= PHP_VERSION ?></div>
                    <div><strong>cURL Extension:</strong> <?= extension_loaded('curl') ? '✅ Available' : '❌ Not available' ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 