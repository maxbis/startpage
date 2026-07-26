<?php
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Startpage - Bookmarklet</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    
    <main class="wp-page-shell">
        <div class="wp-page-stack">
        <div class="wp-page-header wp-page-header--centered">
            <h1 class="wp-page-title">📌 Add to Startpage</h1>
            <p class="wp-page-lead">Quickly save any website to your startpage</p>
        </div>

        <section class="wp-panel wp-panel--section">
            <h2 class="wp-section-title">Method 1: Bookmarklet (Recommended)</h2>
            
            <div class="wp-section-stack">
                <div class="wp-callout wp-callout--info">
                    <h3 class="wp-subsection-title">Step 1: Configure your startpage URL</h3>
                    <div class="wp-field">
                        <label for="startpage-url" class="wp-label">Your Startpage URL:</label>
                        <input type="url" id="startpage-url" 
                                value="<?php
                                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                    $requestUri = $_SERVER['REQUEST_URI'] ?? '/tools/bookmarklet.php';
                                    $currentUrl = $scheme . '://' . $host . $requestUri;
                                    // Remove /tools/bookmarklet.php and replace with /app/
                                    $startpageUrl = preg_replace('/\/tools\/bookmarklet\.php$/', '/app/', $currentUrl);
                                    // If we're not in a tools subdirectory, just use the domain with /app/
                                    if ($startpageUrl === $currentUrl) {
                                        $startpageUrl = $scheme . '://' . $host . '/app/';
                                    }
                                    echo $startpageUrl;
                                ?>"
                                class="wp-input"
                                placeholder="https://yourdomain.com">
                    </div>
                    <button onclick="generateBookmarklet()" class="wp-button wp-button--primary">
                        Generate Bookmarklet
                    </button>
                </div>

                <div class="wp-callout wp-callout--success">
                    <h3 class="wp-subsection-title">Step 2: Drag this button to your bookmarks bar</h3>
                    <a href="#" id="bookmarklet-link" 
                       class="wp-button wp-button--success">
                        📌 Add to Startpage
                    </a>
                </div>

                <div class="wp-callout">
                    <h3 class="wp-subsection-title">Step 3: How to use</h3>
                    <ol class="wp-prose-list">
                        <li>Navigate to any website you want to save</li>
                        <li>Click the "📌 Add to Startpage" bookmark in your bookmarks bar</li>
                        <li>A popup will open with the current page details pre-filled</li>
                        <li>Choose a category and click "Add Bookmark"</li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="wp-panel wp-panel--section">
            <h2 class="wp-section-title">Method 2: Manual URL Entry</h2>
            
            <div class="wp-callout wp-callout--success">
                <h3 class="wp-subsection-title">Quick Add Form</h3>
                <p class="wp-supporting-text">Copy the URL of any website and paste it in the form below:</p>
                
                <form id="quickAddForm" class="wp-stack">
                    <div class="wp-field">
                        <label for="quick-url" class="wp-label">Website URL</label>
                        <input type="url" id="quick-url" placeholder="https://example.com" 
                               class="wp-input" required>
                    </div>
                    <button type="submit" class="wp-button wp-button--success wp-button--block">
                        📌 Add to Startpage
                    </button>
                </form>
            </div>
        </section>

        <section class="wp-panel wp-panel--section">
            <h2 class="wp-section-title">Method 3: Browser Integration</h2>
            
            <div class="wp-columns">
                <div class="wp-callout wp-callout--warning">
                    <h3 class="wp-subsection-title">Chrome/Edge</h3>
                    <ol class="wp-prose-list">
                        <li>Right-click on your bookmarks bar</li>
                        <li>Select "Add page"</li>
                        <li>Name: "Add to Startpage"</li>
                        <li>URL: Copy the bookmarklet code below</li>
                    </ol>
                </div>
                
                <div class="wp-callout">
                    <h3 class="wp-subsection-title">Firefox</h3>
                    <ol class="wp-prose-list">
                        <li>Right-click on your bookmarks toolbar</li>
                        <li>Select "New Bookmark"</li>
                        <li>Name: "Add to Startpage"</li>
                        <li>Location: Copy the bookmarklet code below</li>
                    </ol>
                </div>
            </div>

            <div class="wp-callout">
                <h4 class="wp-subsection-title">Bookmarklet Code:</h4>
                <code id="bookmarklet-code" class="wp-code-block">
                    // Configure your startpage URL first
                </code>
            </div>
        </section>

        <div class="wp-button-row wp-button-row--end">
            <a href="../app/" class="wp-button wp-button--secondary">
                ← Back to Startpage
            </a>
        </div>
        </div>
    </main>

    <script>
        function generateBookmarklet() {
            const startpageUrl = document.getElementById('startpage-url').value.trim();
            if (!startpageUrl) {
                alert('Please enter your startpage URL');
                return;
            }
            
            // Remove trailing slash if present
            const cleanUrl = startpageUrl.replace(/\/$/, '');
            
            const bookmarkletCode = `javascript:(function(){var href=location.href||'';if(!/^https?:/i.test(href)){location.href='${cleanUrl}';return;}var url=encodeURIComponent(window.location.href);var title=encodeURIComponent(document.title);var desc=encodeURIComponent(document.querySelector('meta[name="description"]')?.content||'');window.open('${cleanUrl}?add=1&url='+url+'&title='+title+'&desc='+desc,'_blank','width=580,height=600');})();`;    

            document.getElementById('bookmarklet-link').href = bookmarkletCode;
            document.getElementById('bookmarklet-code').textContent = bookmarkletCode;
        }

        // Handle quick add form
        document.getElementById('quickAddForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const url = document.getElementById('quick-url').value;
            const startpageUrl = document.getElementById('startpage-url').value.trim().replace(/\/$/, '');
            if (url && startpageUrl) {
                window.open(startpageUrl + '?add=1&url=' + encodeURIComponent(url), '_blank', 'width=500,height=600');
            } else {
                alert('Please configure your startpage URL first');
            }
        });

        // Generate bookmarklet on page load
        window.addEventListener('load', function() {
            generateBookmarklet();
        });
    </script>
</body>
</html>
