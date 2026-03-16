# DMOZ MVP Starter - Phase 3

This is a lightweight PHP + MariaDB/MySQL + Bootstrap project scaffold for a DMOZ-style human-edited directory MVP.

## Phase 3 additions

- shared header and footer includes for easier site-wide updates
- public search page with pagination
- paginated category listings
- stronger editor site management and category management
- import batch UI skeleton for future DMOZ staging work
- audit log writes for approvals, category edits, site edits, and import batch creation

## Requirements

- PHP 8.1+
- Apache with `mod_rewrite`
- MariaDB 10.6+ or MySQL 8+

## Quick start

1. Copy the project into your local web root.
2. Point Apache document root to the `public/` directory.
3. Create a database named `dmoz_mvp`.
4. Import `database/schema.sql`.
5. Update `config/database.php` with your local credentials.
6. Visit the site in your browser.

## Default editor login

- Username: `admin`
- Password: `password123`

## Useful URLs

- `/` home page
- `/submit` public submission form
- `/search`
- `/category/computers`
- `/category/computers/php`
- `/editor`
- `/editor/categories`
- `/editor/sites`
- `/editor/submissions`
- `/editor/imports`

## Notes

- Header and footer are now split into `app/Views/layouts/header.php` and `app/Views/layouts/footer.php`.
- Duplicate URL detection is based on a normalized URL strategy.
- Category `path` remains the canonical public route key.
- The import and spider tables are included, but only the first import management UI is wired up yet.
- Full descendant path rebuilding for moved/renamed categories is intentionally left for the next phase.
