START TRANSACTION;

INSERT INTO certificate_sequences (
    client_id,
    category_id,
    last_number,
    created_at,
    updated_at
)
SELECT
    client_id,
    category_id,
    MAX(sequence_number),
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM inspections
WHERE sequence_number IS NOT NULL
GROUP BY client_id, category_id
ON DUPLICATE KEY UPDATE
    last_number = GREATEST(certificate_sequences.last_number, VALUES(last_number)),
    updated_at = VALUES(updated_at);

COMMIT;
