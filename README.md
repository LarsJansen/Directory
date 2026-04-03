# Internet History Directory

A production-minded, framework-free PHP + MySQL directory focused on the history of the internet, early web culture, old services, protocols, preserved documents, and text archives.

This project is intentionally simple:

- plain PHP
- MySQL / MariaDB
- Apache with mod_rewrite
- Bootstrap-based UI
- no ORM
- no framework
- overwrite-friendly deployment

The directory is designed to support both classic external links and preserved text resources such as BBS documents imported from archive dumps.

## Project structure

The repository contains two connected parts.

### 1. Main directory app

Located in the application root.

Responsible for:

- public browsing
- category pages
- resource pages
- editor login and moderation
- category management
- site and text archive management
- import batch visibility
- duplicate review and dead-site workflow

### 2. CLI / importer scripts

Located in `/scripts/`.

Responsible for:

- preparing import SQL
- running check and maintenance jobs
- exporting snapshots
- converting external archive material into directory-ready rows

The importer writes SQL for manual import into the live database. There is no API layer between the importer and the app.

## Core concepts

### Categories

Categories are hierarchical and stored as a full path.

Examples:

- `internet-history`
- `text-archives/bulletin-board-system`
- `services/search-engines`

`categories.path` is unique.

### Sites table

The `sites` table stores both normal links and preserved text resources.

Important fields include:

- `category_id`
- `title`
- `slug`
- `url`
- `normalized_url`
- `description`
- `content_type` (`link` or `text`)
- `body_text`
- `text_source_note`
- `text_author`
- `source_type`
- `source_key`
- `import_batch_id`
- `status`
- `is_active`
- `is_featured`
- `created_at`
- `updated_at`

### Content types

#### `link`
A normal external website entry.

#### `text`
A preserved text archive resource rendered internally by the directory.

## Current feature set

### Public side

- homepage with featured and latest content
- category browsing
- breadcrumb navigation
- search
- static pages via `/pages/{slug}`
- external resource pages
- text archive resource pages with preserved plain-text rendering

### Editor side

- editor login
- submission queue
- category create / edit / move / merge / delete
- site and text archive creation and editing
- site filtering by status, category, sort, and content type
- bulk site actions
- duplicate URL review
- dead-site queue
- site check visibility
- audit log visibility
- quick site actions from the main site listing

### Option A patch included in this version

This codebase now includes the first editor power tools pass:

- one-click feature / unfeature actions from the site list
- one-click activate / deactivate actions from the site list
- one-click flag-for-review action from the site list
- one-click mark-dead action from the site list
- bulk actions for feature, unfeature, flag, and mark dead
- filtered result summary cards in the site manager

## Requirements

- PHP 8.1+
- Apache with `mod_rewrite`
- MySQL 8+ or MariaDB 10.6+

## Local setup

1. Copy the project into your web root.
2. Point Apache at the `public/` directory.
3. Create a database.
4. Import `database/schema.sql`.
5. Apply any required migrations from `database/migrations/`.
6. Update `config/database.php` with your local database credentials.
7. Visit the site in your browser.

## Default editor login

The seed account in the starter schema is:

- Username: `admin`
- Password: `password123`

Change this immediately on any non-disposable environment.

## Apache notes

The app expects rewrite routing through `public/.htaccess`.

Typical local development flow on WAMP:

- repo checkout in `C:\wamp64\www\Directory`
- Apache vhost or local host entry pointing to `public/`
- MySQL database imported manually through CLI or phpMyAdmin

## Importer and maintenance scripts

Examples in `/scripts/` include:

- `check_sites.php`
- `export_category_snapshot.php`
- `import_textfiles_bbs.php`
- `strip_category_html.php`
- `strip_site_html.php`

These are intended to be run manually from the command line.

For detailed examples and Windows command lines, see `/scripts/README.md`.

## Text archive import notes

The current text archive workflow supports BBS-style imports sourced from local dump folders outside the repository.

Typical characteristics:

- parse archive index files
- generate slugs from source names or titles
- store full plain text in `body_text`
- save provenance in `source_key`
- keep imports deduplicable and reversible by batch

The repository deliberately excludes bulky archive storage.

## Storage policy

The `/storage` style directories are not intended to live in the repository.

Examples:

- `/storage/`
- `/storage.bbs/`
- archive dump folders for textfiles or DMOZ exports

These should stay on disk locally and be ignored by Git.

## Git workflow

Recommended approach:

- keep the repository clean and code-only
- do not commit archive dumps, SQL exports, logs, or temporary output
- use `.gitignore` for local-only folders and generated files
- deploy by overwriting files from a known-good working tree or zip

## Development style for this project

- keep solutions simple
- respect current structure
- avoid unnecessary abstractions
- preserve data integrity
- prefer overwrite-ready file replacements over hand patching
- do not assume storage dumps are present in the repo

## Deployment style

This project is being maintained with a practical overwrite workflow.

When preparing changes, use the latest full application zip as the source of truth, excluding large storage dump folders. Deliverables should be real files that can be copied over the current working tree.

## Useful URLs

Examples depend on your host configuration, but the important routes are:

- `/`
- `/search`
- `/category`
- `/category/{path}`
- `/pages/{slug}`
- `/editor`
- `/editor/sites`
- `/editor/categories`
- `/editor/submissions`
- `/editor/imports`
- `/editor/audit`

## Notes for future work

Likely next stages include:

- stronger text archive curation workflows
- smarter duplicate detection for text resources
- recursive archive import handling
- featured collections and better public landing pages
- richer editor tooling for high-volume review
