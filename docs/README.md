# StartPage documentation

## Purpose

This directory documents the StartPage application as implemented in the source tree. Documentation is organized by feature and mirrors the directory that owns each feature.

## Documentation map

- [Application flow](app/application-flow.md) describes the authenticated start page, page selection, and server-rendered data.
- [Authentication and accounts](app/authentication-and-accounts.md) describes login, registration, remember-me sessions, administration, and verification scaffolding.
- [Bookmark, category, and page API](api/content-management-api.md) describes the JSON endpoints used by the browser.
- [Client modules](assets/js/client-modules.md) describes module loading and browser-side responsibilities.
- [Index data service](includes/services/index-data-service.md) describes the queries and view model behind the main page.
- [Favicon resolution](includes/favicon/favicon-resolution.md) describes discovery, caching, fallbacks, and refresh behavior.
- [Database schema](database/schema.md) describes persisted entities, ownership, setup, and runtime-created support tables.
- [Browser workflow tests](tests/browser-workflow.md) describes the current Selenium test and its limitations.

## Source-of-truth rule

- When documentation and code disagree, then the code is authoritative.
- When behavior changes, then update the feature document under the directory that owns the changed source.
- When a feature spans directories, then put its primary document beside the main implementation and link related files from it.

