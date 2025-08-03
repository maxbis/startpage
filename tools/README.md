# Tools

This directory contains utility scripts and tools for the StartPage application.

## Files

### Bookmarklet
- `bookmarklet.php` - Generates bookmarklet code for adding bookmarks from any website
- `test-bookmarklet.html` - Test page for the bookmarklet functionality

### Cache Management
- `cache-manager.php` - Web interface for managing favicon cache (view, refresh, cleanup)
- `get-favicon.php` - Standalone favicon discovery and caching utility

## Usage

### Bookmarklet
1. Open `bookmarklet.php` in your browser
2. Drag the generated bookmarklet to your browser's bookmarks bar
3. Use it on any website to quickly add bookmarks to your StartPage

### Cache Manager
1. Open `cache-manager.php` in your browser
2. View cache statistics and manage favicon cache
3. Refresh individual favicons or clean up old cache files

### Favicon Tool
1. Use `get-favicon.php?url=https://example.com` to test favicon discovery
2. Returns JSON with favicon URL and metadata 