-- Back up the database first. Run this migration once in phpMyAdmin.
-- NULL means archive-only; 1-5 are the five homepage portfolio slots.
-- The unique key allows only one project in each slot while allowing many NULL values.
ALTER TABLE projects
    ADD COLUMN featured_order TINYINT UNSIGNED DEFAULT NULL AFTER status,
    ADD UNIQUE KEY uq_projects_featured_order (featured_order);

-- Seed the approved portfolio order. Other project entries remain unchanged and visible in the dashboard/archive.
UPDATE projects
SET featured_order = CASE title
    WHEN 'Fam Station , Kids Room' THEN 1
    WHEN 'The Owl''s Nest Cafe' THEN 2
    WHEN 'Old Town new Memories Resturent' THEN 3
    WHEN 'Raqmi Bank project' THEN 4
    WHEN 'Sanober Yasir Salon & Spa' THEN 5
    ELSE NULL
END
WHERE title IN (
    'Fam Station , Kids Room',
    'The Owl''s Nest Cafe',
    'Old Town new Memories Resturent',
    'Raqmi Bank project',
    'Sanober Yasir Salon & Spa'
);
