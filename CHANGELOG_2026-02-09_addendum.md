# HMS Changes Addendum (2026-02-09)

## Scope
Follow-up fixes for release readiness: UI error visibility, workflow hardening, data integrity, and test utilities.

## Workflow Hardening
- Prevent duplicate active queue rows on appointment check-in.
- Block duplicate pharmacy requests per patient (Pending/Ready).
- Normalize pharmacy request status updates.
- Admission waiting status filters use case-insensitive comparison.

## Reliability
- Pharmacy request creation is now transactional (request + queue status update).

## UI/UX Improvements
- Doctor dashboard shows banners on failed loads (appointments, waiting room, escalations).
- Reception dashboard shows banners on failed loads (appointments, queue list, summary, stats).
- Nurse admission waiting shows clearer status badges.
- Pharmacy lists show banners on failed loads (pending list, nurse requests).

## Data Integrity (Migration Updated)
- Added NOT NULL defaults:
  - `patient_queue.status` default `Waiting`
  - `pharmacy_requests.status` default `Pending`
- Existing migration continues to add indexes and foreign keys.

## Files Touched
- `backend/routes/reception.php`
- `backend/routes/nurse.php`
- `backend/routes/pharmacy.php`
- `backend/migrations/2026_02_09_release_integrity.sql`
- `frontend/pages/doctor/dashboard.html`
- `frontend/pages/reception/dashboard.html`
- `frontend/pages/nurse/dashboard.html`
- `frontend/pages/pharmacy/dashboard.html`

