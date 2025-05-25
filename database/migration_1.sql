-- Index for filtering expenses by user and date
CREATE INDEX IF NOT EXISTS idx_expenses_user_date ON expenses (user_id, date DESC);

-- Index to help with filtering by category (e.g., sum/group by)
CREATE INDEX IF NOT EXISTS idx_expenses_user_category ON expenses (user_id, category);

-- Composite index for checking duplicates (used in CSV import)
CREATE INDEX IF NOT EXISTS idx_expenses_deduplication ON expenses (
    user_id,
    date,
    description,
    amount_cents,
    category
);