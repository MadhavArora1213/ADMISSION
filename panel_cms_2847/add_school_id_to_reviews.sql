USE admission;

-- Add school_id column to reviews table for school reviews
ALTER TABLE reviews ADD COLUMN school_id CHAR(36) NULL AFTER college_id;
ALTER TABLE reviews ADD INDEX idx_reviews_school_id (school_id);

-- Make college_id nullable since school reviews won't have a college_id
ALTER TABLE reviews MODIFY COLUMN college_id CHAR(36) NULL;
