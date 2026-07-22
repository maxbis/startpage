-- Add reliable bookmark usage tracking.
ALTER TABLE bookmarks
    ADD COLUMN IF NOT EXISTS click_count INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_clicked_at DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_bookmarks_last_clicked_at
    ON bookmarks (last_clicked_at);

-- Existing click counts predate last_clicked_at. Use updated_at as a one-time,
-- approximate starting point; future clicks record an exact timestamp.
UPDATE bookmarks
SET last_clicked_at = updated_at
WHERE click_count > 0
  AND last_clicked_at IS NULL;
