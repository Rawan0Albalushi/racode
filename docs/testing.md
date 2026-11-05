# Testing

Feature tests use PHPUnit and an in-memory SQLite database. Vite is disabled inside the test case, and Inertia SSR is turned off in `phpunit.xml`.

```bash
php artisan test
```

On this machine the PHP binary that can run Artisan is the Herd PHP 8.3 executable when `php` on `PATH` is not a working interpreter.

## What is covered

- The root URL redirects to `/ar`, and the locale cookie can choose `/en`.
- The homepage renders in Arabic (RTL) and English (LTR).
- Unknown public pages render the error screen.
- `robots.txt` disallows `/admin`, and responses send the frame and content-type headers.
- Guests are sent to the login screen.
- An active administrator can sign in and out.
- An inactive account cannot sign in.
- Repeated failed logins are rate limited.
- A viewer cannot create a service.
- An editor cannot publish.
- The last active super admin cannot be deactivated or deleted.
- Creating a user does not write the password into the activity log.
- A draft service stays off the public site until it is published, in both locales, and then appears in the sitemap.
- Scheduled content stays hidden until `content:publish-scheduled` runs.
- The contact form stores a valid inquiry and rejects a honeypot, a too-fast submit, and missing consent.
- The media library accepts a PNG and rejects a PHP upload.
- A viewer cannot change settings. Maintenance mode returns 503 to guests and still allows the login screen.

The seeder does not load demonstration content during tests. Tests create the rows they need.

## Frontend check

```bash
npx tsc --noEmit
npm run build
```

Both were run against this codebase after the pages and dashboard were added. Re-run them after interface changes. `npm run build` has to succeed before a production release.
