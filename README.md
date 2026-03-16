# DMOZ MVP Starter - Phase 2

This is a lightweight PHP + MariaDB/MySQL + Bootstrap project scaffold for a DMOZ-style human-edited directory MVP.

## Phase 2 additions

- path-based category URLs such as `/category/computers/php`
- editor category CRUD
- editor site management
- duplicate URL detection using normalized URLs
- improved category pages with breadcrumbs
- import-ready schema still preserved for future DMOZ subset ingestion

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
- `/category/computers`
- `/category/computers/php`
- `/editor`
- `/editor/categories`
- `/editor/sites`
- `/editor/submissions`

## Notes

- Duplicate URL detection is currently based on a simple normalized URL strategy.
- Category `path` is now the canonical public route key, which will help a lot with later DMOZ imports.
- The import and spider tables are included, but no importer or crawler jobs are wired up yet.

## Next logical phase

- first DMOZ import staging screen
- audit log writes for editor actions
- simple CLI spider for live/dead checks
- pagination for category listings and editor tables
