# RACODE | راكود

Bilingual corporate website and content management system for RACODE, a software studio in the Sultanate of Oman. Arabic is the default language. English is available at `/en`.

The public site and the `/admin` dashboard live in this one Laravel application. React pages are rendered through Inertia. There is no separate frontend or API application.

## Requirements

- PHP 8.3 or newer, with `gd`, `pdo_sqlite`, and `pdo_mysql`
- Composer
- Node.js 20 or newer
- A database: SQLite for local work, MySQL for production

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

For local SQLite, set `DB_CONNECTION=sqlite` in `.env` and create `database/database.sqlite` if it does not exist. `.env.example` documents the MySQL variables used in production.

Set the first administrator before seeding. Do not commit the password.

```bash
# .env
ADMIN_EMAIL=admin@racode.om
ADMIN_PASSWORD=choose-a-long-unique-password
```

If `ADMIN_PASSWORD` is empty and `APP_ENV=local`, the seeder generates a password and prints it once.

```bash
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

Open `http://127.0.0.1:8000`. The root path redirects to `/ar` unless a `locale` cookie selects English. Sign in at `/admin/login`.

## Production build

```bash
npm run build
```

Leave `INERTIA_SSR_ENABLED=false` unless you also run `php artisan inertia:start-ssr` after the Vite SSR build. Titles, descriptions, canonical URLs, and hreflang are written in the Blade document so pages stay indexable without that process.

## Scheduled publishing

Content set to publish later is released by:

```bash
php artisan content:publish-scheduled
```

The scheduler runs that command every minute. A production host must run `php artisan schedule:run` every minute, and a queue worker when `QUEUE_CONNECTION=database`:

```bash
php artisan queue:work
```

## Documentation

- [Architecture](docs/architecture.md)
- [Database](docs/database.md)
- [Admin guide](docs/admin-guide.md)
- [Deployment](docs/deployment.md)
- [Testing](docs/testing.md)

## Content boundary

Seeded projects are demonstrations. The site does not invent client names, awards, testimonials, or completed-project statistics. Replace `hello@racode.om`, the logo, and the favicon from the dashboard when the real company details are ready.
