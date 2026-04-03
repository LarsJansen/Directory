# Scripts

This folder contains the CLI scripts used to import, clean, enrich, check, and export data for the Internet History Directory.

The scripts are designed to be run manually from Command Prompt on your local WAMP setup.

## Local environment

Working directory:

```bat
cd C:\wamp64\www\Directory
```

PHP executable:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe
```

MySQL executable:

```bat
C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe
```

Database:

```text
oldweb
```

Local site URL:

```text
http://directory
```

## General usage pattern

Most PHP scripts in this folder are run like this:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\script_name.php [options]
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\enrich_text_descriptions.php --limit=20000 --chunk=250 --dry-run
```

---

## bootstrap.php

Shared bootstrap file for CLI scripts.

What it does:
- loads helpers
- loads app config and DB config
- creates the shared database connection
- registers the autoloader

How to use it:
- you do not run this file directly
- other scripts `require` it

---

## check_sites.php

Checks live website entries over HTTP and stores the result in the site check tables and audit log.

Useful for:
- checking stale listings
- rechecking flagged or dead sites
- running maintenance sweeps on the live directory

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\check_sites.php --limit=50
```

Useful options:
- `--limit=50` number of sites to check
- `--site-id=123` check one specific site
- `--timeout=20` request timeout in seconds
- `--stale-hours=168` only check entries older than this many hours
- `--status=flagged` restrict by directory status
- `--include-inactive` include inactive entries
- `--help` show usage

---

## enrich_text_descriptions.php

Generates cleaner descriptions for imported text resources, especially text archive entries.

Useful for:
- improving rough imported descriptions
- upgrading large text batches after import
- optionally improving titles on weak entries

Example dry run:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\enrich_text_descriptions.php --limit=20000 --chunk=250 --dry-run
```

Live run example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\enrich_text_descriptions.php --limit=20000 --chunk=250
```

Useful options:
- `--limit=20000` maximum rows to process
- `--chunk=250` batch size per query
- `--dry-run` preview only, do not write changes
- `--upgrade-titles=1` also improve titles where needed

---

## export_category_snapshot.php

Exports a plain text snapshot of a category tree and the sites within it.

Useful for:
- reviewing a branch offline
- sharing a category snapshot
- auditing category contents before a big edit

Examples:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\export_category_snapshot.php --path=computers/programming
```

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\export_category_snapshot.php --path=text-archives/bulletin-board-system --status=active,flagged
```

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\export_category_snapshot.php --path=all --output=storage\exports\all_categories_snapshot.txt
```

Useful options:
- `--path=category/path`
- `--id=123`
- `--output=filename.txt`
- `--status=active,flagged`

---

## import_textfiles_bbs.php

Builds an SQL import file from a local Textfiles.com BBS archive dump.

Default base category:

```text
text-archives/bulletin-board-system
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_bbs.php --dry-run
```

Typical live-style export:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_bbs.php --output=storage\exports\textfiles_bbs_import.sql
```

Common options shared by the Textfiles importers:
- `--source=storage/textfiles/bbs`
- `--index=storage/textfiles/bbs/index.html`
- `--output=storage/exports/textfiles_bbs_import.sql`
- `--category=text-archives/bulletin-board-system`
- `--include-dangerous`
- `--min-size=0`
- `--max-size=500000`
- `--limit=500`
- `--flat`
- `--auto-create-categories=0`
- `--dry-run`

---

## import_textfiles_computers.php

Builds an SQL import file from a local Textfiles.com computers archive dump.

Default base category:

```text
text-archives/computers
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_computers.php --dry-run
```

---

## import_textfiles_internet.php

Builds an SQL import file from a local Textfiles.com internet archive dump.

Default base category:

```text
text-archives/internet
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_internet.php --dry-run
```

---

## import_textfiles_magazines.php

Builds an SQL import file from a local Textfiles.com magazines archive dump.

Default base category:

```text
text-archives/magazines
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_magazines.php --dry-run
```

---

## import_textfiles_programming.php

Builds an SQL import file from a local Textfiles.com programming archive dump.

Default base category:

```text
text-archives/programming
```

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\import_textfiles_programming.php --dry-run
```

---

## strip_category_html.php

Cleans HTML out of category descriptions and stores the cleaned plain-text version back into the database.

Useful for:
- legacy imports with HTML in descriptions
- normalising copied descriptions before public display

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\strip_category_html.php --dry-run
```

Useful options:
- `--dry-run`
- `--help`

---

## strip_site_html.php

Cleans HTML out of site descriptions and original descriptions.

Useful for:
- normalising imported site descriptions
- cleaning older rows after schema or importer changes

Example:

```bat
C:\wamp64\bin\php\php8.3.0\php.exe scripts\strip_site_html.php --dry-run
```

Useful options:
- `--dry-run`
- `--help`

---

## Importing generated SQL into MySQL

When a script generates a `.sql` file, you can import it with MySQL CLI like this:

```bat
C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe -u root -p oldweb < storage\exports\textfiles_bbs_import.sql
```

Adjust the username, password prompt, and SQL filename as needed.

---

## Notes

- Run scripts from the project root so relative paths resolve properly.
- Prefer `--dry-run` first on any script that changes data.
- The Textfiles import scripts generate SQL and report files; they do not insert directly into the live database.
- `storage/exports/` is the natural place for generated SQL and snapshot files.
