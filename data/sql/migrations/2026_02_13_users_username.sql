-- Make users username-first (email optional)
-- Compatible with older MySQL versions (no direct ADD COLUMN IF NOT EXISTS support)

SET @db_name := DATABASE();

-- 1) Add username column only if missing
SET @has_username := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'username'
);
SET @sql := IF(
  @has_username = 0,
  'ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Fill username from email where possible
UPDATE users
SET username = SUBSTRING_INDEX(email, '@', 1)
WHERE (username IS NULL OR username = '')
  AND email IS NOT NULL
  AND email <> '';

-- 3) Fallback username for rows still empty
UPDATE users
SET username = CONCAT('user_', id)
WHERE username IS NULL OR username = '';

-- 4) Ensure duplicates become unique
UPDATE users u
JOIN (
  SELECT username
  FROM users
  WHERE username IS NOT NULL AND username <> ''
  GROUP BY username
  HAVING COUNT(*) > 1
) d ON d.username = u.username
SET u.username = CONCAT(u.username, '_', u.id);

-- 5) Make username NOT NULL
ALTER TABLE users
  MODIFY username VARCHAR(80) NOT NULL;

-- 6) Add unique index on username only if missing
SET @has_uq := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'uq_users_username'
);
SET @sql := IF(
  @has_uq = 0,
  'ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7) Make email nullable only if email column exists
SET @has_email := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'email'
);
SET @sql := IF(
  @has_email = 1,
  'ALTER TABLE users MODIFY email VARCHAR(255) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
