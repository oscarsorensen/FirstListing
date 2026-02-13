-- Add optional email field to users table (if missing)
SET @db_name := DATABASE();

SET @has_email := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'email'
);

SET @sql := IF(
  @has_email = 0,
  'ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL UNIQUE AFTER username',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
