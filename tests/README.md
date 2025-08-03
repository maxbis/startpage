# Tests Directory

This directory contains test pages for debugging and testing various functionality of the startpage application.

## Available Tests

### `favicon-test.php`
A comprehensive test page for the favicon discovery functionality.

**Features:**
- ✅ **URL Input Form**: Enter any website URL to test favicon discovery
- ✅ **Visual Results**: Shows the discovered favicon with preview
- ✅ **Detailed Information**: Displays favicon URL, domain, and timestamp
- ✅ **Error Handling**: Shows clear error messages for failed discoveries
- ✅ **Test Examples**: Quick links to test popular websites
- ✅ **Debug Information**: Shows system status and dependencies

**Usage:**
1. Navigate to `http://localhost:8000/tests/favicon-test.php`
2. Enter a website URL (e.g., `https://github.com`)
3. Click "🔍 Discover Favicon"
4. View the results and favicon preview

**Test Examples Included:**
- GitHub, Stack Overflow, Hacker News, Reddit
- Google, Microsoft, Apple, Amazon

**Debug Information:**
- FaviconDiscoverer class availability
- PHP version
- cURL extension status
- Current test URL

## Purpose

These test pages help with:
- **Debugging**: Isolate and test specific functionality
- **Development**: Verify changes work correctly
- **Troubleshooting**: Identify issues with favicon discovery
- **Documentation**: Show how the favicon system works

## Access

All test pages are accessible via:
- `http://localhost:8000/tests/favicon-test.php`

## Security Note

These test pages are for development and debugging purposes. In production, consider:
- Adding authentication requirements
- Restricting access to admin users only
- Moving to a separate development environment 