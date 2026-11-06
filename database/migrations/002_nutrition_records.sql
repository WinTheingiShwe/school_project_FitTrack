-- Apply after the base schema. Existing local tables and data are preserved.
-- 4. Convenience Products
CREATE TABLE IF NOT EXISTS convenience_products (
    id SERIAL PRIMARY KEY,
    store VARCHAR(30) NOT NULL,
    category VARCHAR(100),
    product_name VARCHAR(200) NOT NULL,
    price VARCHAR(50),
    calories DECIMAL(8,2),
    protein DECIMAL(8,2),
    fat DECIMAL(8,2),
    carbohydrates DECIMAL(8,2),
    salt DECIMAL(8,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Nutrition Records
CREATE TABLE IF NOT EXISTS nutrition_records (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    nutrition_date DATE NOT NULL,
    food_name VARCHAR(200) NOT NULL,
    calories DECIMAL(8,2) NOT NULL,
    protein DECIMAL(8,2),
    source VARCHAR(30) NOT NULL,
    store VARCHAR(30),
    product_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_nutrition_records_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_nutrition_records_product
        FOREIGN KEY (product_id)
        REFERENCES convenience_products(id)
        ON DELETE SET NULL,

    CONSTRAINT chk_nutrition_source
        CHECK (source IN ('manual', 'convenience_store')),

    CONSTRAINT chk_nutrition_store
        CHECK (
            store IS NULL
            OR store IN ('FamilyMart', 'LAWSON', '7-Eleven')
        ),

    CONSTRAINT chk_nutrition_calories
        CHECK (calories >= 0)
);

CREATE INDEX IF NOT EXISTS nutrition_records_user_date_idx ON nutrition_records (user_id, nutrition_date);
