-- Reversible manually with:
-- ALTER TABLE users DROP COLUMN sessions_revoked_at;
ALTER TABLE users
  ADD COLUMN sessions_revoked_at DATETIME NULL AFTER last_login_at,
  ADD INDEX idx_users_sessions_revoked_at (sessions_revoked_at);
