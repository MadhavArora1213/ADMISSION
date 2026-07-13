-- Hero banner priority slots (1-5) for featured/paid institutions
-- Priority 1 = shown first in the hero carousel, 5 = shown last
-- NULL = not in hero banner

ALTER TABLE colleges ADD COLUMN hero_priority INT NULL DEFAULT NULL AFTER is_featured;
ALTER TABLE universities ADD COLUMN hero_priority INT NULL DEFAULT NULL AFTER is_featured;
ALTER TABLE schools ADD COLUMN hero_priority INT NULL DEFAULT NULL AFTER is_featured;

-- Index for fast lookup (MySQL allows multiple NULLs in unique index)
CREATE UNIQUE INDEX idx_colleges_hero_priority ON colleges(hero_priority);
CREATE UNIQUE INDEX idx_universities_hero_priority ON universities(hero_priority);
CREATE UNIQUE INDEX idx_schools_hero_priority ON schools(hero_priority);
