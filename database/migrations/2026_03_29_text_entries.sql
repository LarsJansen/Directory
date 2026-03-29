ALTER TABLE sites
    MODIFY COLUMN url VARCHAR(2083) NULL,
    MODIFY COLUMN normalized_url VARCHAR(2083) NULL,
    ADD COLUMN content_type ENUM('link','text') NOT NULL DEFAULT 'link' AFTER slug,
    ADD COLUMN body_text MEDIUMTEXT NULL AFTER description,
    ADD COLUMN text_source_note VARCHAR(255) NULL AFTER body_text,
    ADD COLUMN text_author VARCHAR(255) NULL AFTER text_source_note;
