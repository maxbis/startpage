# Application flow

## Purpose

The application presents an authenticated, multi-page bookmark dashboard. PHP builds the initial view for the selected page; browser modules then provide search, modals, navigation, drag-and-drop, context menus, and CRUD actions.

## Location

- `index.php` redirects requests at the project root to `app/`.
- `app/index.php` authenticates the request and renders the dashboard.
- `includes/services/index-data-service.php` loads the current user's pages, categories, and bookmarks.
- `includes/templates/modals/` contains the modal markup included by the dashboard.

## Inputs/Outputs

Inputs:

- An authenticated PHP session or a valid `startpage_remember_token` cookie.
- The optional `startpage_current_page_id` cookie, which is accepted only when the page belongs to the current user.
- Bookmarklet query parameters: `add=1`, `url`, `title`, and `desc`.

Outputs:

- A server-rendered HTML dashboard for one selected page.
- Browser configuration on `window` for favicon behavior and bookmark color mappings.
- DOM data attributes used by the JavaScript modules for edit, move, reorder, and navigation actions.

## Flow/Behavior

1. The root entry point computes its installation base path and redirects to `app/`.
2. `app/index.php` starts the configured session and calls `requireAuth()`.
3. `IndexDataService` chooses the first page by sort order unless the current-page cookie names another page owned by the user.
4. When the user has no pages, the service creates `My Startpage` and the default `Work`, `Personal`, and `Tools` categories.
5. The service loads categories and bookmarks for the selected page and loads all pages and categories for navigation and modal controls.
6. PHP renders the page, category, bookmark, context-menu, and modal markup.
7. `assets/js/app.js` loads the client modules sequentially after `DOMContentLoaded`.
8. Client modules call the JSON endpoints under `api/` and update or reload the rendered view.

Bookmarklet behavior:

- When `add=1` and `url` is a valid HTTP or HTTPS URL, then the add-bookmark flow is prefilled.
- When the URL is missing, malformed, or uses another scheme, then bookmarklet mode is disabled.

## Edge Cases/Failure Modes

- When authentication fails, then the dashboard redirects to `app/login.php`.
- When a current-page cookie references another user's page or a deleted page, then it is ignored and replaced with the selected valid page.
- When default-page creation throws, then the data service falls back to page ID `1`; this fallback is not ownership-aware and can still lead to an empty or inconsistent view.
- Page selection is stored in a one-year cookie, while the rendered data remains filtered by the authenticated user.
- The application depends on CDN-hosted SortableJS and Tailwind CSS; those features or styling can degrade when the CDN is unavailable.

## Related Files

- [Index data service](../includes/services/index-data-service.md)
- [Client modules](../assets/js/client-modules.md)
- [Content management API](../api/content-management-api.md)
- [Authentication and accounts](authentication-and-accounts.md)
