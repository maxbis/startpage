# Authentication and accounts

## Purpose

The account flow supports username/password login, optional persistent login tokens, self-registration, logout, password changes, and an administrator-only user-management screen.

## Location

- `app/login.php`, `app/logout.php`, and `app/register.php`
- `app/admin.php` and `app/verify.php`
- `includes/auth_functions.php`
- `includes/session_config.php`
- `includes/rate_limiter.php`
- `includes/email_verification.php`
- `api/change-password.php`

## Inputs/Outputs

Login accepts form fields `username`, `password`, and optional `remember_me`. A successful login stores `user_id` and `username` in the session and redirects to `index.php`.

Registration accepts:

- `username`: 3 to 50 letters, digits, underscores, or hyphens; reserved account names are rejected.
- `password` and `confirm_password`: matching values with at least 8 characters.
- `email`: currently collected but not used to gate or verify the created account.
- `website`: a honeypot that must stay empty.
- `timestamp`: the form must be submitted before it is one hour old.

Persistent authentication uses the `startpage_remember_token` cookie. Tokens are stored with user agent, IP address, creation time, and expiry.

## Flow/Behavior

Login:

1. The submitted username is loaded with a prepared query.
2. `password_verify()` checks the password hash.
3. The session is populated and its identifier is regenerated.
4. When remember-me is selected, a 60-day token is created and written to an HTTP-only, `SameSite=Lax` cookie.
5. At most ten recent remember tokens are retained per user.

Authentication checks:

1. A remember token is checked before the existing session.
2. A valid token recreates or refreshes the session.
3. An invalid or expired token is removed from the browser.
4. If neither token nor session authenticates the request, `requireAuth()` redirects to login.

Registration:

1. `RateLimiter` permits five registration attempts per IP address per hour.
2. The form's bot and validation checks run.
3. The user is inserted with a password hash.
4. A default page and three default categories are inserted.

Administration:

- User ID `1` is treated as the administrator.
- The admin page can create users, reset non-admin passwords, and delete non-admin users.
- Database cascades remove a deleted user's pages, categories, bookmarks, and remember tokens.

Password change:

- The endpoint requires the current password and matching new password fields.
- The API minimum is 6 characters, which is less strict than registration's 8-character minimum.
- A successful change deletes all remember tokens for the user.

## Edge Cases/Failure Modes

- Remember-me cookies are explicitly created with `secure=false`, so they can travel over HTTP. Deployments should terminate HTTPS and update this behavior before treating the cookie as transport-secure.
- Session lifetime is configured for 30 days and remember tokens for 60 days.
- `RateLimiter` and `EmailVerification` create their own tables at runtime; these tables are absent from `database/setup.sql`.
- Registration includes `email_verification.php` but does not create or send a verification token. `app/verify.php` only works for tokens created by some other caller.
- The verification email implementation contains placeholder domain and sender values.
- Logout deletes the presented token and all tokens for the current user, ending persistent login on every device.
- Some JSON endpoints use `requireAuth()` and therefore redirect to HTML login, while others return a JSON 401 response.

## Related Files

- [Database schema](../database/schema.md)
- [Content management API](../api/content-management-api.md)
- [Application flow](application-flow.md)
