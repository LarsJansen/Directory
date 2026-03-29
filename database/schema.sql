SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS site_checks;
DROP TABLE IF EXISTS category_source_map;
DROP TABLE IF EXISTS imported_site_staging;
DROP TABLE IF EXISTS import_batches;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS sites;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    slug VARCHAR(150) NOT NULL,
    path VARCHAR(500) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    source_type ENUM('manual','dmoz_import') NOT NULL DEFAULT 'manual',
    source_key VARCHAR(255) NULL,
    import_batch_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY uq_categories_path (path(191)),
    KEY idx_categories_parent (parent_id),
    KEY idx_categories_slug (slug),
    KEY idx_categories_import_batch (import_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    url VARCHAR(2083) NULL,
    normalized_url VARCHAR(2083) NULL,
    content_type ENUM('link','text') NOT NULL DEFAULT 'link',
    description TEXT NOT NULL,
    body_text MEDIUMTEXT NULL,
    text_source_note VARCHAR(255) NULL,
    text_author VARCHAR(255) NULL,
    language_code VARCHAR(10) NULL,
    country_code VARCHAR(10) NULL,
    status ENUM('active','dead','flagged') NOT NULL DEFAULT 'active',
    source_type ENUM('manual','submission','dmoz_import') NOT NULL DEFAULT 'manual',
    source_key VARCHAR(255) NULL,
    original_title VARCHAR(255) NULL,
    original_description TEXT NULL,
    original_url VARCHAR(2083) NULL,
    import_batch_id INT UNSIGNED NULL,
    submitted_by_user_id INT UNSIGNED NULL,
    is_reviewed TINYINT(1) NOT NULL DEFAULT 1,
    approved_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sites_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_sites_submitted_by FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_sites_category (category_id),
    KEY idx_sites_status (status),
    KEY idx_sites_source_type (source_type),
    KEY idx_sites_import_batch (import_batch_id),
    UNIQUE KEY uq_sites_normalized_url (normalized_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposed_category_id INT UNSIGNED NULL,
    submitter_name VARCHAR(120) NULL,
    submitter_email VARCHAR(120) NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(2083) NOT NULL,
    description TEXT NOT NULL,
    notes TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by_user_id INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_site_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissions_category FOREIGN KEY (proposed_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_submissions_user FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_submissions_site FOREIGN KEY (created_site_id) REFERENCES sites(id) ON DELETE SET NULL,
    KEY idx_submissions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    details JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL,
    source_version VARCHAR(100) NULL,
    batch_label VARCHAR(150) NOT NULL,
    notes TEXT NULL,
    imported_by_user_id INT UNSIGNED NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    status ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
    total_categories INT UNSIGNED NOT NULL DEFAULT 0,
    total_sites INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_import_batches_user FOREIGN KEY (imported_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE categories
    ADD CONSTRAINT fk_categories_import_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL;

ALTER TABLE sites
    ADD CONSTRAINT fk_sites_import_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL;

CREATE TABLE imported_site_staging (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id INT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NULL,
    raw_category_path TEXT NULL,
    raw_title VARCHAR(255) NULL,
    raw_url VARCHAR(2083) NULL,
    raw_description TEXT NULL,
    normalized_url VARCHAR(2083) NULL,
    mapped_category_id INT UNSIGNED NULL,
    import_status ENUM('pending','mapped','imported','skipped','duplicate','error') NOT NULL DEFAULT 'pending',
    status_message TEXT NULL,
    created_site_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imported_site_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_imported_site_category FOREIGN KEY (mapped_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_imported_site_created_site FOREIGN KEY (created_site_id) REFERENCES sites(id) ON DELETE SET NULL,
    KEY idx_imported_site_batch (import_batch_id),
    KEY idx_imported_site_status (import_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE category_source_map (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('dmoz_import') NOT NULL,
    source_path TEXT NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_source_map_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_checks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    check_type ENUM('http_status','backlink','outbound_links','whois','content_hash') NOT NULL,
    result_status ENUM('ok','warn','fail') NOT NULL,
    result_data JSON NULL,
    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_site_checks_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    KEY idx_site_checks_site (site_id),
    KEY idx_site_checks_type (check_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, email, password_hash, role)
VALUES ('admin', 'admin@example.local', '$2y$12$rBrwWZi7ch1wqTpRAcPdHOG0g2hMnLgX.sREdxJQ5FeKyQSCayAgO', 'admin');

INSERT INTO categories (parent_id, slug, path, name, description, sort_order) VALUES
(NULL, 'computers', 'computers', 'Computers', 'Software, programming, web tools, and related resources.', 1),
(NULL, 'arts', 'arts', 'Arts', 'Art, design, writing, culture, and creative resources.', 2),
(NULL, 'science', 'science', 'Science', 'Science references, educational resources, and research tools.', 3);

INSERT INTO categories (parent_id, slug, path, name, description, sort_order)
SELECT id, 'directories', CONCAT(path, '/directories'), 'Directories', 'Human-edited and curated web directories.', 1
FROM categories WHERE slug = 'computers';

INSERT INTO categories (parent_id, slug, path, name, description, sort_order)
SELECT id, 'search', CONCAT(path, '/search'), 'Search', 'Search engines, indexes, and discovery tools.', 2
FROM categories WHERE slug = 'computers';

INSERT INTO categories (parent_id, slug, path, name, description, sort_order)
SELECT id, 'php', CONCAT(path, '/php'), 'PHP', 'PHP software, documentation, and tooling.', 3
FROM categories WHERE slug = 'computers';

INSERT INTO sites (
    category_id, title, slug, url, normalized_url, description,
    status, source_type, original_title, original_description, original_url,
    is_reviewed, approved_at, is_active, created_at, updated_at
)
SELECT id, 'Curl', 'curl', 'https://curl.se', 'https://curl.se',
       'Project site for curl and libcurl.',
       'active', 'manual', 'Curl', 'Project site for curl and libcurl.', 'https://curl.se',
       1, NOW(), 1, NOW(), NOW()
FROM categories WHERE path = 'computers' LIMIT 1;

INSERT INTO sites (
    category_id, title, slug, url, normalized_url, description,
    status, source_type, original_title, original_description, original_url,
    is_reviewed, approved_at, is_active, created_at, updated_at
)
SELECT id, 'Bootstrap', 'bootstrap', 'https://getbootstrap.com', 'https://getbootstrap.com',
       'Frontend toolkit for responsive interfaces.',
       'active', 'manual', 'Bootstrap', 'Frontend toolkit for responsive interfaces.', 'https://getbootstrap.com',
       1, NOW(), 1, NOW(), NOW()
FROM categories WHERE path = 'computers' LIMIT 1;

INSERT INTO sites (
    category_id, title, slug, url, normalized_url, description,
    status, source_type, original_title, original_description, original_url,
    is_reviewed, approved_at, is_active, created_at, updated_at
)
SELECT id, 'PHP', 'php', 'https://www.php.net', 'https://www.php.net',
       'Official PHP language documentation and downloads.',
       'active', 'manual', 'PHP', 'Official PHP language documentation and downloads.', 'https://www.php.net',
       1, NOW(), 1, NOW(), NOW()
FROM categories WHERE path = 'computers/php' LIMIT 1;
