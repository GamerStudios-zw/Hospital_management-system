# HMS Changes Summary (2026-02-09)

## Scope
Release-readiness fixes and workflow corrections across admissions, discharge, and pharmacy.

## Key Fixes
- Set frontend API base URLs to same-origin to avoid CORS issues.
- Doctor discharge now issues an approved command so nursing can discharge.
- Discharge completion now removes patients from urgent/triage/admission queues (case-insensitive status handling).
- Admission workflow statuses added: Waiting Pharmacy, Ready for Admission.
- Pharmacy pending list now includes Waiting Pharmacy and Ready for Admission.
- Doctor attribution for pharmacy: ensured doctor_assigned is set at consult completion; pharmacy resolves doctor name from users.
- Nurse requests list now matches status case-insensitively.
- Admission waiting list status badges now show: Admission Pending, Waiting Pharmacy, Ready for Admission.

## Backend Changes
- `backend/routes/nurse.php`
  - Discharge: status update uses `LOWER(status)` for robustness.
  - Admission waiting list includes Waiting Pharmacy/Ready for Admission with case-insensitive filter.
  - Pharmacy request sets patient_queue status to Waiting Pharmacy.
- `backend/routes/pharmacy.php`
  - Pending prescriptions join to latest queue row, preferring rows with doctor_assigned.
  - Pending list includes Waiting Pharmacy and Ready for Admission statuses.
  - Added doctor_name (resolved from users) for display.
  - Nurse requests filter uses `LOWER(status)`.
  - Dispense: when last pending/external prescription is dispensed, status moves to Ready for Admission.
- `backend/routes/doctor.php`
  - On consult completion and refer, set doctor_assigned if missing.

## Frontend Changes
- `frontend/assets/js/app.js`
  - API base URLs set to same-origin.
- `frontend/pages/doctor/dashboard.html`
  - Discharge create uses status: approved.
- `frontend/pages/pharmacy/dashboard.html`
  - Doctor column displays resolved doctor name or fallback label.
- `frontend/pages/nurse/dashboard.html`
  - Admission waiting status badges for Waiting Pharmacy/Ready for Admission.

## Migration Added
- `backend/migrations/2026_02_09_release_integrity.sql`
  - Adds indexes: patient_queue(patient_id, status), prescriptions(patient_id), pharmacy_requests(status)
  - Adds foreign keys: patient_queue.doctor_assigned -> users.id, prescriptions.patient_id -> patients.id, pharmacy_requests.patient_id -> patients.id

## Smoke Test Script
- `scripts/smoke_test.ps1`
  - Lightweight endpoint check (with or without token).

## Notes
- Canonical status casing is Title Case; filtering uses `LOWER()` for safety.
- Pharmacy doctor attribution relies on latest queue row with doctor_assigned.