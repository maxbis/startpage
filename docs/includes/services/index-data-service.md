# Index data service

## Purpose

`IndexDataService` isolates the database reads and view-model preparation used by the main dashboard.

## Location

- `includes/services/index-data-service.php`
- Consumer: `app/index.php`

## Inputs/Outputs

Constructor inputs:

- A configured PDO connection.
- The authenticated user ID.

Public outputs:

- `getCurrentPageId()` returns the selected owned page ID and establishes the current-page cookie.
- `getBookmarkletData()` returns modal state and prefilled URL, title, and description values.
- `getCategoriesAndBookmarks()` returns indexed category view models plus bookmark arrays keyed by category ID.
- `getCurrentPageName()` returns the selected page's name or `My Start Page`.
- `getAllPages()` returns owned page IDs and names in display order.
- `getCategoriesByPage()` returns owned categories grouped by page for form controls.

Category preferences are decoded from JSON:

- `cat_width`: values 1 through 4 map to 200, 240, 274, and 300 pixels.
- `no_descr`: suppresses bookmark descriptions when set.
- `show_fav`: controls favicon visibility.

## Flow/Behavior

1. The service selects the user's first page by `sort_order`, then `id`.
2. A current-page cookie overrides that selection only after an ownership query succeeds.
3. If no page exists, a default page and three categories are inserted.
4. The category/bookmark query joins pages and filters every entity by the current user and current page.
5. Category preference JSON is converted to render-ready values.
6. Stored favicon paths are normalized to renderable values.
7. Flat query rows are grouped into category and bookmark collections for the template.

## Edge Cases/Failure Modes

- Missing or invalid preference JSON falls back field-by-field to normal width, visible descriptions, and visible favicons.
- Categories without bookmarks remain in the output with an empty bookmark array because the query uses a left join.
- Default page/category creation is not wrapped in a transaction, so a partial insert is possible if a later insert fails.
- Creation failures fall back to page ID `1`, even when that page is not owned by the current user.
- `getCurrentPageId()` must run before methods that depend on the internal current page ID.
- Bookmarklet `urlError` is currently always an empty string even when the URL is rejected.

## Related Files

- [Application flow](../../app/application-flow.md)
- [Database schema](../../database/schema.md)
- [Favicon resolution](../favicon/favicon-resolution.md)
