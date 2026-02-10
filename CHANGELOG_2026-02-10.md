# Changelog — 2026-02-10

## Summary
This release consolidates multiple workflow improvements across Doctor, Nurse, Nurse Aid, Pharmacy, Reception, and Admin modules, introduces self‑hosted fonts, standardizes notifications, adds basic monitoring/tests, and improves mobile usability.

## Features
- **Nurse Aid module** with triage + daily vitals workflow and clinical notes.
- **Urgent care flow** improvements, including admission waiting list and nurse handoff.
- **Pharmacy requests** workflow for nurse → pharmacy medication pickup.
- **Self‑hosted fonts** (Inter, Poppins, Roboto) and unified font family via `--app-font`.
- **Banner notifications** replacing native browser popups across the UI.
- **Monitoring**: `/health` endpoint with DB + realtime status and a watcher script.
- **Basic test runner**: combined DB connection test + API smoke test.
- **Mobile responsive layouts** for Admin, Nurse, Nurse Aid, Doctor, Pharmacy, Reception, and IT dashboards.

## Fixes
- **Doctor dashboard**: appointment handling, consult validation, and tab‑driven modules consolidated in one dashboard.
- **Nurse/Nurse Aid**: validations for vitals capture and overdue status refresh.
- **Admission & discharge** flows tightened to reflect admitted patients only.
- **UI cleanup**: removal of missing action buttons, refined tab switching, and responsive table wrappers.
- **Backend cleanups**: reduced server errors in queue/bed admission operations.

## Database Updates
- `patient_queue.status` enum expanded (includes admission/urgent care states).
- `prescriptions.medicine_id` now nullable.
- `prescriptions.status` enum includes `External`.
- Added `pharmacy_requests` table.
- Removed reliance on `beds.updated_at` where it was not present.

## Monitoring & Tests
- **Health endpoint**: `backend/index.php/health`
- **Watcher**: `scripts/monitor_watch.ps1`
- **Test runner**: `scripts/run_tests.ps1` (includes `scripts/smoke_test.ps1` + `backend/db_connection_test.php`)

## Notes
- Fonts are now served locally from `frontend/assets/fonts/` and `frontend/assets/css/fonts.css`.
- All modules use the unified `--app-font` setting.
- Native `alert()` calls replaced with `notify()` banner messages.
