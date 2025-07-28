<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📌 Add to Startpage - Bookmarklet</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-300 text-gray-800 min-h-screen font-sans">
    
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-blue-600 mb-4">📌 Add to Startpage</h1>
            <p class="text-xl text-gray-600">Quickly save any website to your startpage</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Method 1: Bookmarklet (Recommended)</h2>
            
            <div class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Step 1: Configure your startpage URL</h3>
                    <div class="mb-4">
                        <label for="startpage-url" class="block text-sm font-medium text-gray-700 mb-1">Your Startpage URL:</label>
                        <input type="url" id="startpage-url" 
                                value="<?= preg_replace('/bookmarklet\.php$/', '', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
                                placeholder="https://yourdomain.com">
                    </div>
                    <button onclick="generateBookmarklet()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                        Generate Bookmarklet
                    </button>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-green-800 mb-2">Step 2: Drag this button to your bookmarks bar</h3>
                    <a href="#" id="bookmarklet-link" 
                       class="inline-block bg-green-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-600 transition shadow-md">
                        📌 Add to Startpage
                    </a>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">Step 3: How to use</h3>
                    <ol class="list-decimal list-inside space-y-1 text-gray-700">
                        <li>Navigate to any website you want to save</li>
                        <li>Click the "📌 Add to Startpage" bookmark in your bookmarks bar</li>
                        <li>A popup will open with the current page details pre-filled</li>
                        <li>Choose a category and click "Add Bookmark"</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Method 2: Manual URL Entry</h2>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="font-semibold text-green-800 mb-2">Quick Add Form</h3>
                <p class="text-green-700 mb-4">Copy the URL of any website and paste it in the form below:</p>
                
                <form id="quickAddForm" class="space-y-4">
                    <div>
                        <label for="quick-url" class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                        <input type="url" id="quick-url" placeholder="https://example.com" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" required>
                    </div>
                    <button type="submit" class="w-full bg-green-500 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-600 transition">
                        📌 Add to Startpage
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Method 3: Browser Integration</h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-semibold text-yellow-800 mb-2">Chrome/Edge</h3>
                    <ol class="list-decimal list-inside space-y-1 text-yellow-700 text-sm">
                        <li>Right-click on your bookmarks bar</li>
                        <li>Select "Add page"</li>
                        <li>Name: "Add to Startpage"</li>
                        <li>URL: Copy the bookmarklet code below</li>
                    </ol>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="font-semibold text-purple-800 mb-2">Firefox</h3>
                    <ol class="list-decimal list-inside space-y-1 text-purple-700 text-sm">
                        <li>Right-click on your bookmarks toolbar</li>
                        <li>Select "New Bookmark"</li>
                        <li>Name: "Add to Startpage"</li>
                        <li>Location: Copy the bookmarklet code below</li>
                    </ol>
                </div>
            </div>

            <div class="mt-6 bg-gray-100 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">Bookmarklet Code:</h4>
                <code id="bookmarklet-code" class="text-xs bg-white p-3 rounded border block overflow-x-auto">
                    // Configure your startpage URL first
                </code>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="index.php" class="inline-block bg-gray-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-600 transition">
                ← Back to Startpage
            </a>
        </div>
    </div>

    <script>
        function generateBookmarklet() {
            const startpageUrl = document.getElementById('startpage-url').value.trim();
            if (!startpageUrl) {
                alert('Please enter your startpage URL');
                return;
            }
            
            // Remove trailing slash if present
            const cleanUrl = startpageUrl.replace(/\/$/, '');
            
            const bookmarkletCode = `javascript:(function(){var url=encodeURIComponent(window.location.href);var title=encodeURIComponent(document.title);var desc=encodeURIComponent(document.querySelector('meta[name="description"]')?.content||'');window.open('${cleanUrl}/index.php?add=1&url='+url+'&title='+title+'&desc='+desc,'_blank','width=800,height=700');})();`;
            
            document.getElementById('bookmarklet-link').href = bookmarkletCode;
            document.getElementById('bookmarklet-code').textContent = bookmarkletCode;
        }

        // Handle quick add form
        document.getElementById('quickAddForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const url = document.getElementById('quick-url').value;
            const startpageUrl = document.getElementById('startpage-url').value.trim().replace(/\/$/, '');
            if (url && startpageUrl) {
                window.open(startpageUrl + '/index.php?add=1&url=' + encodeURIComponent(url), '_blank', 'width=500,height=600');
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