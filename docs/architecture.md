# Architecture

RACODE is one Laravel application. Public pages and the admin dashboard are Inertia responses that resolve to React components under `resources/js/pages`.

## Request flow

1. `routes/web.php` serves the public site under `/{locale}` where the locale is `ar` or `en`.
2. `routes/admin.php` serves `/admin` behind authentication, an active-user check, and Spatie permission middleware.
3. Controllers return `Inertia::render(...)` with database data. The browser does not call a separate REST API for page rendering.
4. `HandleInertiaRequests` sets the locale, then shares navigation, brand settings, the signed-in user, permissions, and flash messages.
5. `resources/views/app.blade.php` prints the title, description, canonical URL, hreflang, Open Graph tags, and JSON-LD before React hydrates.

## Publishing

Editable records keep a working copy in normal columns. The public site reads `published_payload`.

- **Save** updates the working copy and marks unpublished changes when a published version already exists.
- **Publish** copies a snapshot into `published_payload`, commits a pending slug, and stores a revision.
- **Schedule** stores `scheduled_payload` and `scheduled_for`. The previous published snapshot stays public until `content:publish-scheduled` runs.
- **Preview** (`?preview=1`) shows the working copy to a user who can update that content type.

Homepage sections are rows on `page_sections`. Publishing the homepage page snapshots those rows into the page payload. Section content is a validated structure (headings, buttons, items, an image id). It is not executable HTML.

Article and page bodies are block lists: heading, paragraph, list, or quote. Each block stores Arabic and English text.

## Localization

Arabic is the default. Public URLs are `/ar/...` and `/en/...`. The `locale` cookie remembers the choice used by the `/` redirect. Admin language is stored on the user and in the session.

Translatable fields are JSON objects shaped as `{ "ar": "...", "en": "..." }`. `App\Support\Translations` returns the string for the active locale when content is sent to the public site.

## Authorization

Roles are `super-admin`, `content-manager`, `editor`, and `viewer`. Permissions are listed in `config/racode.php`. An active super admin passes `Gate::before`, and the seeder also syncs every permission onto that role so database permission queries match.

Policies cover pages, services, projects, articles, FAQs, and users. Other admin modules check the same permission names in the controller. The last active super admin cannot be deactivated, demoted, or deleted.

## Media and contact

Uploads are limited to JPEG, PNG, WebP, and GIF at 5 MB. Files are stored on the `public` disk and resized with GD. A file that is still referenced cannot be deleted.

The contact form writes an `inquiries` row. A honeypot, a minimum time on the form, validation, and a per-IP rate limit reject spam. Email is sent only when `MAIL_MAILER` is a delivering mailer and a notification address is configured. Users who can view inquiries also receive a database notification.

## Frontend

- `resources/js/pages/website` — public pages
- `resources/js/pages/admin` — dashboard
- `resources/js/components` — shared interface pieces
- `resources/js/i18n/index.ts` — interface copy for Arabic and English
- `resources/css/app.css` — brand tokens, type, and reduced-motion behavior

Motion is used for the homepage entrance. GSAP draws the code mark. Both stay off when the visitor prefers reduced motion.
