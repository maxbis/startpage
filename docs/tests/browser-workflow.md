# Browser workflow test

## Purpose

The Selenium workflow exercises the visible application flow for login, context menus, category and bookmark management, page management, moving content, and cleanup.

## Location

- `tests/test_complete_workflow.py`
- `tests/requirements.txt`
- `tests/check_database_content.php`

## Inputs/Outputs

The workflow currently uses these inputs:

- Login URL `http://localhost/msp/app/login.php`.
- Username and password entered interactively at startup (`input` / `getpass`).
- A locally available Chrome browser and matching Selenium driver.

The test prints progress and failure messages to standard output and controls a visible Chrome window. It does not produce a structured test report.

## Flow/Behavior

1. Open a visible Chrome session and log in.
2. Open the empty-space context menu.
3. Create a generated category.
4. Add BBC and Google bookmarks through the quick-add modal.
5. Create a page and move the category to it.
6. Exercise edit and delete flows and verify expected DOM changes.
7. Close the browser in final cleanup.

`tests/check_database_content.php` is a diagnostic script rather than an automated assertion suite. It prints users, pages, categories, and selected ownership data from the configured database.

## Edge Cases/Failure Modes

- The Selenium file is an executable workflow, not a pytest-style isolated test fixture.
- It mutates the configured database and uses fixed generated names; interrupted or repeated runs can leave conflicting data.
- Fixed mouse coordinates, sleeps, visible text, emoji labels, and specific DOM IDs make the test sensitive to layout and copy changes.
- It requires a running web server at a hard-coded path and an existing account whose credentials are entered at startup.
- It fetches external BBC and Google pages during bookmark creation, so network behavior can affect timing and metadata.
- `check_database_content.php` reads active database records and assumes IDs `1` for some diagnostics; it must not be exposed as a public production endpoint.

## Related Files

- [Application flow](../app/application-flow.md)
- [Content management API](../api/content-management-api.md)
- [Database schema](../database/schema.md)
