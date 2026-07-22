# Database schema

## Purpose

The MariaDB/MySQL schema stores users, persistent login tokens, pages, categories, and bookmarks while using `user_id` ownership fields to isolate application data.

## Location

- `database/setup.sql`: complete current base schema.
- `database/auth_setup.sql`: legacy standalone authentication setup and default admin seed.
- `includes/db.php`: PDO connection configuration.
- `includes/rate_limiter.php` and `includes/email_verification.php`: runtime-created support tables.

## Inputs/Outputs

Core entities:

- `users`: unique username and password hash.
- `remember_tokens`: persistent authentication token, device metadata, and expiry linked to a user.
- `pages`: named ordered dashboards owned by a user.
- `categories`: ordered groups linked logically to a page and owned by a user; display preferences are stored as JSON text.
- `bookmarks`: ordered URLs linked to a category and owned by a user, with optional description, favicon, color, cumulative `click_count`, and exact `last_clicked_at` usage time. The dashboard maps this timestamp to four progressively shorter recency arcs: within 3 days, within 14 days, within 3 months, and older or never used.

Runtime-created entities:

- `rate_limits`: attempts keyed by IP address and action.
- `email_verifications`: email verification tokens linked to users.

## Flow/Behavior

For a clean database:

1. Create a database named `startpage`, or update `includes/db.php` to use the intended database.
2. Import `database/setup.sql` once.
3. Create the initial user with an application registration flow or an explicit password hash appropriate for the environment.
4. Start the application; registration and verification code creates its support tables when those classes are instantiated.

Ownership and deletion rules:

- When a user is deleted, then their pages, categories, bookmarks, and remember tokens are removed through user foreign-key cascades.
- When a category is deleted directly at the database level, then its bookmarks retain ownership but their `category_id` becomes `NULL`.
- When a bookmark is opened from the dashboard, global search, or open-all action, then its click count and last-clicked timestamp are updated.
- The application prevents deletion of non-empty categories, so the database `SET NULL` behavior is normally a last-resort integrity rule.
- Page-to-category integrity is enforced by application queries; `setup.sql` does not define a foreign key from `categories.page_id` to `pages.id`.

## Edge Cases/Failure Modes

- Do not import both setup files into a clean database: `setup.sql` already creates `users` and `remember_tokens`, so importing `auth_setup.sql` afterward attempts to create duplicate tables.
- `auth_setup.sql` inserts an `admin` user with a documented default password and is unsuitable as an unchanged production seed.
- `includes/db.php` contains local default credentials in source and prints connection exception details to the response. Production configuration should move secrets out of the repository and avoid exposing database errors.
- Because `categories.page_id` has no foreign key, direct database changes can create orphan categories or cross-user page references. Application ownership joins hide some, but not all, malformed data.
- Because `bookmarks.category_id` is nullable, direct category deletion can leave bookmarks that the main inner joins and category rendering no longer expose.
- `database/README.md` references migrations that are not present in the current tree and incorrectly suggests running both setup scripts for initial setup; this document reflects the current SQL files instead.

## Related Files

- [Authentication and accounts](../app/authentication-and-accounts.md)
- [Index data service](../includes/services/index-data-service.md)
- [Content management API](../api/content-management-api.md)
