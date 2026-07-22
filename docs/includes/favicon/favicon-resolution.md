# Favicon resolution

## Purpose

The favicon subsystem discovers an icon for a bookmark URL, caches usable image bytes locally, normalizes stored paths for rendering, and supplies deterministic fallbacks when discovery fails.

## Location

- `includes/favicon/icon-resolver.php`
- `includes/favicon/favicon-cache.php`
- `includes/favicon/favicon-discoverer.php`
- `includes/favicon/favicon-config.php`
- `api/refresh-favicon.php`
- `assets/js/modules/favicon-management.js`
- Diagnostic tools under `tools/`

## Inputs/Outputs

Primary input is a bookmark URL or host. `IconResolver::resolveForUrl()` returns resolution metadata including:

- `favicon_url`: a cached local path or generated fallback.
- `source_url` and `source`: where the selected candidate came from.
- `cached`: whether an existing cached icon was used.
- `normalized_url` and `final_url`: URL processing and redirect results.
- `failure_reason`: why a fallback was needed, when applicable.

Supported cache extensions are ICO, PNG, JPEG, GIF, SVG, and WebP. Cache keys combine a normalized host/path identity with a short SHA-1 suffix.

## Flow/Behavior

1. Normalize the bookmark URL and establish its origin.
2. Return a non-expired cached file unless refresh is forced.
3. Fetch the bookmark page and discover icon links and manifests.
4. Probe root icon paths and manifest locations as additional candidates.
5. Resolve relative candidate URLs against the page, base element, or manifest.
6. Score candidates by source, path, declared size, format, response type, and relationship to the page origin.
7. Store the best valid image response in `cache/favicons/`.
8. When no remote candidate is usable, return a deterministic generated SVG placeholder or configured external fallback.

The regular cache lifetime is 30 days. Resolution has an overall time budget of about six seconds, extended to twelve seconds in debug mode, while individual network operations are bounded by the remaining budget.

## Edge Cases/Failure Modes

- Remote sites may block the resolver, redirect unexpectedly, omit icon metadata, return HTML for an image URL, or respond after the timeout.
- When the PHP DOM, cURL, or filesystem capabilities required by a probe are unavailable, then discovery can degrade to fallback behavior.
- Stored cache paths are normalized before rendering; stale paths whose files no longer exist are replaced by fallback output.
- Forced refresh deletes or replaces cache entries for the normalized key and can still end in a generated fallback.
- Debug mode can expose detailed remote URL and response diagnostics and should be enabled only when troubleshooting.
- Cache cleanup and clearing mutate files under `cache/favicons/`; the web server process needs appropriate directory permissions.

## Related Files

- [Content management API](../../api/content-management-api.md)
- [Client modules](../../assets/js/client-modules.md)
- `tools/cache-manager.php`
- `tools/get-favicon.php`
- `tools/favicon-test.php`
