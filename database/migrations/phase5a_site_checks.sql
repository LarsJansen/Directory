-- Optional Phase 5A performance migration
-- Run this only if your local schema does not already include these indexes.

ALTER TABLE site_checks
    ADD KEY idx_site_checks_checked_at (checked_at);

ALTER TABLE site_checks
    ADD KEY idx_site_checks_site_checked (site_id, checked_at);
