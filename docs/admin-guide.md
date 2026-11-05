# Admin guide

Sign in at `/admin/login`. The interface follows the account language. The language control in the header switches Arabic and English and saves that choice on the user.

The overview counts pages, services, projects, articles, new inquiries, and recent activity from the database. It does not show estimated analytics.

## Publishing

Most content has two steps.

1. **Save draft** stores the working copy. A published URL does not change yet.
2. **Publish** makes that copy the public version.
3. **Schedule** keeps the current public version until the chosen time. The server must run the scheduler so `content:publish-scheduled` can release it.
4. **Preview** opens the public URL with `?preview=1`. Only someone who can edit that content type sees the draft.

A revision list on the edit screen can restore an earlier snapshot. Restoring a published record publishes the restored snapshot when the user is allowed to publish.

System pages (home, about, services, work, technologies, insights, contact, privacy, terms) cannot be deleted. Custom pages are available at `/ar/p/{slug}` and `/en/p/{slug}`.

## Homepage

Open the homepage from Pages. The builder adds a section from the predefined types, then edits:

- Arabic and English heading and description
- primary and secondary buttons
- an image from the media library
- repeating items, used by the process and AI sections
- layout and light or dark theme
- visibility

Drag a section or use the move buttons to reorder it. Save each section, then publish the page. Hidden sections stay out of the public homepage.

## Services, work, and insights

Services, projects, and articles have Arabic and English fields, a cover from the media library, SEO fields, and the same draft, publish, and schedule actions.

Projects stay labeled as demonstrations unless that flag is turned off for a real engagement you are prepared to describe. Result text is kept only when **verified results** is checked. Do not enter client names, awards, or numbers that have not been confirmed.

Insights use the block editor: headings, paragraphs, lists, and quotes. Categories and tags are created from the insights index.

## Media

Upload JPEG, PNG, WebP, or GIF files up to 5 MB. Add Arabic and English alt text. Replace keeps the same media id so existing content keeps its reference. Delete is refused while something still uses the file.

Run `php artisan storage:link` so uploaded files are reachable at `/storage`.

## Inquiries

The public contact form stores name, email, phone, optional company, project type, message, and consent. The inbox can be filtered, assigned, annotated, and exported as CSV by someone with export permission. A submission is saved only after validation succeeds. The form does not report success for a rejected message.

## Navigation, settings, and people

Navigation replaces the header and footer lists in one save. Paths must be a site path such as `/services` or an `https` URL.

Settings cover the logo, favicon, the five brand colors, the legal name, contact details, social links, footer text, default SEO, maintenance mode, and optional analytics. Analytics accepts a GA4 id (`G-...`) or a Plausible domain. Maintenance mode hides the public site from guests. Administrators can still sign in.

Roles:

- **Super admin** can manage people and every module. The last active super admin cannot be removed.
- **Content manager** can publish and change site settings, but cannot manage users.
- **Editor** can draft content and upload media, but cannot publish or change settings.
- **Viewer** can read the dashboard and content, but cannot change it.

Activity records who changed what. It does not store passwords.
