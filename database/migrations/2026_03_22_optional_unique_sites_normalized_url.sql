-- Optional migration: enforce unique normalized_url values on sites.
-- Run the duplicate check first. If this returns rows, resolve them before applying the ALTER TABLE.
--
-- SELECT normalized_url, COUNT(*) AS duplicate_count
-- FROM sites
-- WHERE normalized_url IS NOT NULL AND normalized_url <> ''
-- GROUP BY normalized_url
-- HAVING COUNT(*) > 1
-- ORDER BY duplicate_count DESC, normalized_url ASC;

ALTER TABLE sites
    DROP INDEX idx_sites_normalized_url,
    ADD UNIQUE KEY uq_sites_normalized_url (normalized_url(191));
