-- Add gender column to users table
USE hospital_db;

SET @tbl := 'users';
SET @col := 'gender';
SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND column_name = @col
);
SET @sql := IF(@exists = 0,
  CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN gender VARCHAR(20) NULL AFTER full_name'),
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

