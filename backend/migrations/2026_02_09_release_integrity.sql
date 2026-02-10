-- Release integrity migration for hospital_db
-- Applies safe indexes + foreign keys if missing

USE hospital_db;

-- 1) Indexes
-- patient_queue(patient_id)
SET @idx_name := 'idx_patient_queue_patient_id';
SET @tbl := 'patient_queue';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('CREATE INDEX ', @idx_name, ' ON ', @tbl, ' (patient_id)'),
    'SELECT 1')
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND index_name = @idx_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- patient_queue(status)
SET @idx_name := 'idx_patient_queue_status';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('CREATE INDEX ', @idx_name, ' ON ', @tbl, ' (status)'),
    'SELECT 1')
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND index_name = @idx_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- prescriptions(patient_id)
SET @idx_name := 'idx_prescriptions_patient_id';
SET @tbl := 'prescriptions';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('CREATE INDEX ', @idx_name, ' ON ', @tbl, ' (patient_id)'),
    'SELECT 1')
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND index_name = @idx_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pharmacy_requests(status)
SET @idx_name := 'idx_pharmacy_requests_status';
SET @tbl := 'pharmacy_requests';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('CREATE INDEX ', @idx_name, ' ON ', @tbl, ' (status)'),
    'SELECT 1')
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND index_name = @idx_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Foreign keys
-- patient_queue.doctor_assigned -> users.id
SET @fk_name := 'fk_patient_queue_doctor_assigned';
SET @tbl := 'patient_queue';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('ALTER TABLE ', @tbl, ' ADD CONSTRAINT ', @fk_name,
           ' FOREIGN KEY (doctor_assigned) REFERENCES users(id) ON DELETE SET NULL'),
    'SELECT 1')
  FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = @tbl
    AND constraint_name = @fk_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Defaults / NOT NULL (safe)
-- patient_queue.status -> NOT NULL DEFAULT 'Waiting'
SET @tbl := 'patient_queue';
SET @col := 'status';
SET @coltype := (
  SELECT column_type
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND column_name = @col
  LIMIT 1
);
SET @sql := CONCAT(
  'UPDATE ', @tbl, ' SET ', @col, ' = ''Waiting'' WHERE ', @col, ' IS NULL OR ', @col, ' = '''''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := CONCAT(
  'ALTER TABLE ', @tbl, ' MODIFY ', @col, ' ', @coltype, ' NOT NULL DEFAULT ''Waiting'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pharmacy_requests.status -> NOT NULL DEFAULT 'Pending'
SET @tbl := 'pharmacy_requests';
SET @col := 'status';
SET @coltype := (
  SELECT column_type
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND column_name = @col
  LIMIT 1
);
SET @sql := CONCAT(
  'UPDATE ', @tbl, ' SET ', @col, ' = ''Pending'' WHERE ', @col, ' IS NULL OR ', @col, ' = '''''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := CONCAT(
  'ALTER TABLE ', @tbl, ' MODIFY ', @col, ' ', @coltype, ' NOT NULL DEFAULT ''Pending'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- prescriptions.patient_id -> patients.id
SET @fk_name := 'fk_prescriptions_patient_id';
SET @tbl := 'prescriptions';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('ALTER TABLE ', @tbl, ' ADD CONSTRAINT ', @fk_name,
           ' FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE'),
    'SELECT 1')
  FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = @tbl
    AND constraint_name = @fk_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pharmacy_requests.patient_id -> patients.id
SET @fk_name := 'fk_pharmacy_requests_patient_id';
SET @tbl := 'pharmacy_requests';
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    CONCAT('ALTER TABLE ', @tbl, ' ADD CONSTRAINT ', @fk_name,
           ' FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE'),
    'SELECT 1')
  FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = @tbl
    AND constraint_name = @fk_name
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
