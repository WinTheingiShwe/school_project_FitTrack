-- Apply after database/schema.sql on the published account/weight baseline.
-- Existing local training tables and records are preserved.
CREATE TABLE IF NOT EXISTS training_records (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    training_date DATE NOT NULL,
    training VARCHAR(100) NOT NULL,
    duration INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS training_records_user_date_idx
    ON training_records (user_id, training_date);
