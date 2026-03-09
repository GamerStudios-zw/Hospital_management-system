# Changelog Addendum - 2026-02-25

## Summary
This addendum records queue-flow updates completed on February 25, 2026.

## Scope
- Commit: `e7b08c9`
- Message: `Gate Nurse Aid queue updates behind search pull and simplify reception flow table`

## Implemented Changes

### 1) Nurse Aid triage queue now requires explicit Nurse Aid pull
- File: `backend/routes/nurse_aid.php`
- Changes:
  - `stats` and `triage_queue` now only include rows with `queue_origin = 'nurse_aid_search'`.
  - `pull_registered_patient` now marks existing active triage rows with `queue_origin = 'nurse_aid_search'`.
  - New queue rows created from Nurse Aid search are inserted with `queue_origin = 'nurse_aid_search'`.
- Outcome:
  - Triage table updates only after Nurse Aid searches and queues the patient.

### 2) Schema support for queue origin tracking
- File: `backend/utils/DbSchema.php`
- Changes:
  - Added migration guard in `ensureNurseModules()` to add:
    - `patient_queue.queue_origin` (`VARCHAR(50)`)
    - index `idx_patient_queue_origin_status (queue_origin, status)`
- Outcome:
  - Queue source can be distinguished without breaking existing data.

### 3) Reception dashboard flow table simplified
- File: `frontend/pages/reception/dashboard.html`
- Changes:
  - Removed `Doctor` column from "Active Patient Flow (Queue)" header.
  - Removed `doctor_assigned` cell from flow row rendering.
- Outcome:
  - Flow table now shows only Time, Patient, and Status.

## Validation Performed
- `php -l backend/routes/nurse_aid.php` -> no syntax errors.
- `php -l backend/utils/DbSchema.php` -> no syntax errors.
- Confirmed `patient_queue.queue_origin` exists after schema ensure.

## Changed Files
- `backend/routes/nurse_aid.php`
- `backend/utils/DbSchema.php`
- `frontend/pages/reception/dashboard.html`

