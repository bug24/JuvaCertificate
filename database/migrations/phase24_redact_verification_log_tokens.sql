-- Review and back up verification_logs before applying this migration.
-- It irreversibly removes historical full verification tokens from log search text.
START TRANSACTION;

UPDATE verification_logs
SET searched_reference = CONCAT(LEFT(searched_reference, 12), '...')
WHERE searched_reference REGEXP '^[A-Fa-f0-9]{64}$';

COMMIT;
