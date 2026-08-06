# Changelog - 2026-02-12

## Summary
This changelog records updates completed on February 12, 2026 for API stability, Admin dashboard data accuracy, and Doctor module consolidation.

## Backend Changes

### 1) Users API JSON parse failure fix
- File: `backend/routes/users.php`
- Change: Updated `GROUP BY` to include `u.gender` in `/users` list query.
- Why: Prevent SQL errors on strict MySQL modes (for example `ONLY_FULL_GROUP_BY`) that can emit PHP/HTML error output and break frontend JSON parsing.

### 2) Admin staff performance now reflects live DB session presence
- File: `backend/routes/admin.php`
- Endpoint: `GET /admin/staff_perf`
- Changes:
  - Ensured session schema/table availability (`AuthMiddleware::ensureSessionColumns`, `AuthMiddleware::ensureSessionTable`).
  - Added live active-session presence aggregation from `users` + `user_sessions`.
  - Combined presence result using `max(presentByShifts, presentBySessions)`.
  - Added role-presence fallback from live sessions when shift-based role presence is empty.
- Why: Top Admin overview tiles should use real database session state, not only current shift window overlap.

## Frontend Changes

### 3) Admin overview metric source cleanup
- File: `frontend/pages/admin/dashboard.html`
- Changes:
  - Removed metric-tile overwrite logic from `loadUsers()`.
  - Kept overview tiles (`No of Staff`, `No of Doctors`, `No of Nurses`, `Staff Present`, `Staff Utilization %`) sourced from `/admin/staff_perf` via `loadStaffPerformance()`.
- Why: Avoid race conditions where `loadUsers()` and `loadStaffPerformance()` disagree.

### 4) Doctor module unified to one entry page
- File: `frontend/pages/doctor/dashboard.html`
- Changes:
  - Added hash-based tab deep-linking support (`#queue`, `#appointments`, `#directory`, `#timeline`, `#tasks`, `#escalations`, `#discharge`, `#handover`, `#roster`).
  - Added initial hash activation on page load.
  - Updated tab switching to sync URL hash.
- Why: Consolidate multiple doctor pages into a single module shell.

### 5) Legacy doctor pages removed
- Deleted files:
  - `frontend/pages/doctor/appointments.html`
  - `frontend/pages/doctor/my_patients.html`
  - `frontend/pages/doctor/reports.html`
- Why: Complete unification of Doctor UI under `dashboard.html`.

## Validation Performed
- `php -l backend/routes/users.php` -> no syntax errors.
- `php -l backend/routes/admin.php` -> no syntax errors.

## Notes
- Existing unrelated workspace changes and historical log file updates were left intact.
