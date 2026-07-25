-- Category lifecycle and permissions.
-- Apply once after the base schema on existing installations.

UPDATE certification_categories SET status='inactive' WHERE status='draft';
ALTER TABLE certification_categories
  MODIFY status ENUM('active','inactive','legacy') NOT NULL DEFAULT 'active';

UPDATE roles
SET permissions_json='["dashboard.view","clients.view","clients.manage","equipment.view","equipment.manage","categories.view","categories.create","categories.edit","categories.change_status","categories.manage","inspections.view","inspections.create","inspections.edit","certificates.view","verification.view","reports.view","audit.view","users.manage","roles.view"]'
WHERE slug='operations-admin';

UPDATE roles
SET permissions_json='["dashboard.view","clients.view","equipment.view","categories.view","inspections.view","inspections.create","inspections.edit","certificates.view","verification.view"]'
WHERE slug='inspector';

UPDATE roles
SET permissions_json='["dashboard.view","clients.view","equipment.view","categories.view","inspections.view","inspections.review","certificates.view","certificates.issue","verification.view"]'
WHERE slug='reviewer';

UPDATE certification_categories
SET status='legacy'
WHERE code IN ('LEG-B','LEG-C','LEG-D');
