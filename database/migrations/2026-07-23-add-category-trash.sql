-- Add reversible category deletion.
ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL
    AFTER preferences;

CREATE INDEX IF NOT EXISTS idx_categories_user_deleted_page
    ON categories (user_id, deleted_at, page_id, sort_order);
