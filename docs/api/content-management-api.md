# Content management API

## Purpose

The API endpoints provide authenticated JSON operations for bookmarks, categories, pages, password changes, search data, click tracking, reordering, and favicon refreshes.

## Location

- `api/*.php`
- Browser callers in `assets/js/modules/*.js`
- Shared authentication in `includes/auth_functions.php`

## Inputs/Outputs

Requests send JSON bodies and responses use JSON with a `success` boolean. The browser treats a false value or a failed request as an operation error.

Bookmark endpoints:

- `add.php`: requires `url` and `category_id`; accepts `title`, `description`, and integer `color`; returns the new `id`.
- `edit.php`: requires `id`, `title`, `url`, and `category_id`; accepts `description`, `favicon_url`, and integer `color`.
- `delete-bookmark.php`: requires `id`.
- `reorder.php`: requires target `category_id` and an ordered `order` array of bookmark IDs.
- `get-all-bookmarks.php`: returns all owned bookmarks with category and page names for global search.
- `track_click.php`: requires bookmark `id` and increments its click counter when the deployed schema contains that field.

Category endpoints:

- `add-category.php`: requires `name`; the target page comes from `startpage_current_page_id`, defaulting to `1`.
- `edit-category.php`: requires `id`, `name`, `page_id`, `width`, `no_description`, and `show_favicon`.
- `delete-category.php`: requires `id` and succeeds only for an empty category.
- `reorder-categories.php`: requires an ordered `order` array of category IDs.

Page endpoints:

- `add-page.php`: requires a unique, non-empty `name` of at most 100 characters.
- `edit-page.php`: requires `id` and `name`.
- `delete-page.php`: requires `id` and returns whether the deleted page was current plus a replacement page ID when needed.

Other endpoints:

- `change-password.php`: requires `current_password`, `new_password`, and `confirm_password`.
- `refresh-favicon.php`: requires `url`; optional query `debug=1` adds resolution diagnostics.

## Flow/Behavior

- When an endpoint mutates owned content, then its update or delete query normally includes the current user ID.
- When bookmarks move across categories, then `reorder.php` updates the target order and compacts the sort order in each source category inside a transaction.
- When categories are reordered, then their array positions become their `sort_order` values.
- When a page is the user's last page, then deletion is rejected.
- When a category contains bookmarks, then deletion is rejected until those bookmarks are moved or deleted.
- When bookmark title or description is omitted during creation, then `add.php` attempts a three-second server-side page fetch to infer metadata.
- When favicon refresh succeeds, then the response includes the renderable URL, source, cache state, normalized/final URL, and any failure reason used for fallback.

## Edge Cases/Failure Modes

- Authentication behavior is inconsistent: some endpoints return JSON status `401`, while endpoints using `requireAuth()` redirect to the login page.
- Application errors do not use one status convention. Some validation failures return HTTP `200` with `success: false`; many caught errors return `500`, including client input errors.
- `add.php` verifies authentication but does not verify that `category_id` belongs to the current user before calculating order and inserting. Callers must not treat the client-side category list as an authorization boundary.
- `reorder-categories.php` verifies category ownership in each update but does not ensure all categories are on the same page.
- Several endpoints accept any HTTP method even though the browser calls them as POST requests. Only some explicitly reject non-POST requests.
- Bookmark URLs and descriptions are truncated to 200 characters. Bookmark title length is not consistently constrained across create and edit paths.
- A server-side metadata or favicon fetch can fail because of timeouts, remote blocking, invalid content, or unavailable PHP URL/cURL features; bookmark creation can still use provided values or a domain-derived title.

## Related Files

- [Client modules](../assets/js/client-modules.md)
- [Authentication and accounts](../app/authentication-and-accounts.md)
- [Favicon resolution](../includes/favicon/favicon-resolution.md)
- [Database schema](../database/schema.md)
