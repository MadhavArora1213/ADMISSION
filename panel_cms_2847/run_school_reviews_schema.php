USE admission;

-- Add school_id column to reviews table for school reviews
ALTER TABLE reviews ADD COLUMN school_id CHAR(36) NULL AFTER college_id;
ALTER TABLE reviews ADD INDEX idx_reviews_school_id (school_id);

-- Make college_id nullable since school reviews won't have a college_id
ALTER TABLE reviews MODIFY COLUMN college_id CHAR(36) NULL;

-- Drop the foreign key constraint on college_id so we can have school reviews without a college
-- (We'll handle this carefully since the constraint name may vary)
SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'reviews' AND COLUMN_NAME = 'college_id' AND CONSTRAINT_NAME LIKE 'fk_%' LIMIT 1);
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE reviews DROP FOREIGN KEY ', @fk_name), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key that allows NULL college_id
ALTER TABLE reviews ADD FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE;
