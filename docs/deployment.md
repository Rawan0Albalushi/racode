# Deployment

Deploy this repository as a normal Laravel application. Do not split the React site into a second service. The steps below are the preparation. They do not purchase or start hosting.

## Environment

Copy `.env.example` to the server environment.

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- `APP_LOCALE=ar`
- `APP_KEY` from `php artisan key:generate --show`
- MySQL `DB_*` values
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `FILESYSTEM_DISK=local` and a linked `public` disk
- `INERTIA_SSR_ENABLED=false` unless a Node SSR process is supervised
- `ADMIN_EMAIL` and a unique `ADMIN_PASSWORD` for the first seed only
- Mail variables only if a real mailer will send inquiry notifications

Secrets stay in the host environment. They are not committed.

## Build and release

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Seed once on an empty database, after `ADMIN_PASSWORD` is set:

```bash
php artisan db:seed --force
```

Do not re-seed a production database that already has editorial changes unless you intend to refresh the seeded records.

## Processes

The web server points at `public/` and serves the application over HTTPS. Trust the proxy headers your host uses so Laravel generates `https` URLs.

Run one queue worker:

```bash
php artisan queue:work --sleep=1 --tries=3
```

Run the scheduler every minute:

```bash
php artisan schedule:run
```

That releases scheduled pages, services, projects, and articles.

Optional server rendering:

```bash
npm run build
php artisan inertia:start-ssr
```

Set `INERTIA_SSR_ENABLED=true` only while that process is running. The Blade document already exposes the indexable metadata if SSR is off.

## Railway or a conventional server

On Railway, use a PHP service for the Laravel app and a MySQL plugin. The start command serves `public/`. Add a second process for `php artisan queue:work` and a cron for `php artisan schedule:run`. Set the environment variables in the service settings, including `APP_URL` as the public HTTPS origin.

A conventional server uses the same commands behind Nginx or Apache, with PHP-FPM, a systemd queue worker, and a cron entry for the scheduler.

## Backups and logs

Back up the MySQL database and `storage/app` (uploaded media). Keep `storage/logs` for application errors. Do not log inquiry messages beyond what the application already stores in the database, and do not log passwords or mail credentials.

After a restore, run `php artisan storage:link` again if the public link is missing, then confirm `/ar` and `/admin/login`.
