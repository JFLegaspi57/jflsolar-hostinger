# JFL Solar website — Hostinger edition

Laravel 13 · PHP 8.3 · MySQL/MariaDB · Blade · Tailwind CSS 4 (built locally).
Public site (packages, products, projects, quote form) and a staff admin, built to
run on Hostinger web hosting with only File Manager and phpMyAdmin. It reproduces
the earlier Next.js + Supabase prototype (repository `jflsolar`).

- **Deploying and running it on Hostinger:** [docs/HOSTINGER.md](docs/HOSTINGER.md)
- **Project rules:** [AGENTS.md](AGENTS.md) · decisions: [DECISIONS.md](DECISIONS.md) · status: [TASKS.md](TASKS.md) · reviews: [REVIEW.md](REVIEW.md)

## Local development

Requirements: PHP 8.3+ (extensions: pdo_mysql, pdo_sqlite, gd with WebP, exif,
intl, mbstring, fileinfo, zip, curl, openssl), Composer, Node.js 20+, and
optionally MySQL/MariaDB.

```bash
composer install
npm install
cp .env.example .env          # then set APP_ENV=local, APP_DEBUG=true, APP_URL=http://localhost:8000,
                              # SESSION_SECURE_COOKIE=false, and a database (SQLite: DB_CONNECTION=sqlite)
php artisan key:generate
php artisan migrate --seed    # sample content (labelled, hidden in production)
npm run build                 # or: npm run dev
php artisan serve
```

First owner: create `storage/app/admin-recovery.txt` containing a phrase of 12+
characters, then open http://localhost:8000/admin/recover.

## Checks

```bash
php artisan test                          # unit + feature tests (SQLite in memory)
npm run build
# Browser test of the whole admin (needs a fresh migrate --seed, a recovery file and puppeteer-core):
node tests/browser/admin-e2e.mjs http://localhost:8000 storage/app/admin-recovery.txt public/images/hero-rooftop-1280.jpg <some.pdf>
```

## Database schema for phpMyAdmin

`database/schema/jflsolar.sql` is what first installs import (no SSH on the host).
After changing migrations, regenerate it from a scratch MySQL/MariaDB database:

```bash
php artisan migrate:fresh --database=mysql --force    # against an empty scratch database
mariadb-dump --no-data --skip-comments --skip-add-locks --skip-set-charset --compact <db> > structure.sql
mariadb-dump --no-create-info --skip-comments --skip-add-locks --compact <db> migrations > migrations.sql
```

then combine them with the header/footer used in the current file
(`SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0; … = 1;`).

## Release

```bash
PHP_BINARY=php COMPOSER_PHAR=/path/to/composer.phar node scripts/release.mjs
```

Produces `release/jflsolar-release-<date>.zip` (app + `public_html` + schema +
DEPLOY.txt). Test it by extracting it and serving `public_html` before sending it.

## Moving content from the old site

`php artisan jfl:copy-from-supabase --export=storage/app/export` (or `--import`
into the configured database). Needs `SUPABASE_URL` and `SUPABASE_SECRET_KEY` in
`.env` on the developer machine only.
