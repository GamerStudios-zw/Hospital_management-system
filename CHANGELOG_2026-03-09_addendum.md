# Changelog Addendum - 2026-03-09

Generated: 2026-03-09 12:40:10 +02:00
Branch: Tony-dev

## Scope

This addendum captures all local repository changes included for the current push request.

- Tracked files changed (vs HEAD): 30
- Untracked files: 3
- Aggregate diff stats: 30 files changed, 7090 insertions(+), 568 deletions(-)

## Key Functional Updates

### Reception - Registration and Medical Aid
- Made the Register New Patient form scrollable for long capture workflows.
- Enforced medical aid capture card visibility/enablement per registration-type flow.
- Added medical aid provider and cover/plan dropdowns.
- Removed Other from gender options and aligned frontend/backend validation.
- Synced medical aid member name from full name.
- Set issuer/institution defaults for staff/student ID capture.
- Added faculty, programme (searchable and faculty-linked), level (1-7), and semester (1-2) selectors.
- Updated national ID validation to allow 6-8 digits in the middle segment.
- Improved date manual entry handling and formatting logic.
- Locked receptionist queue interactions for protected statuses (checked-in/with doctor/completed paths).

### Backend and Data Layer
- Added schema ensure logic for reception medical aid fields.
- Persisted added medical aid fields in patient create flows.
- Added/updated backend validation rules for medical aid/provider/plan and identity capture constraints.
- Added validation for faculty/programme linkage and academic range checks.
- Updated demo seed data for new schema and capture fields.

### Pharmacy and Other Dashboard Areas
- Added medical aid cover guidance cards to pharmacy dashboard.
- Included additional pending updates across admin/doctor/nurse/pharmacy/users routes and frontend dashboards as listed below.

## Tracked File Status (vs HEAD)

| Status | File |
|---|---|
| A | CHANGELOG_2026-02-23_addendum.html |
| A | CHANGELOG_2026-02-23_addendum.md |
| A | CHANGELOG_2026-02-23_addendum.pdf |
| A | CHANGELOG_2026-02-25_addendum.html |
| A | CHANGELOG_2026-02-25_addendum.md |
| A | CHANGELOG_2026-02-25_addendum.odf |
| A | CHANGELOG_2026-02-25_addendum.odt |
| M | backend/logs/auth_debug.log |
| M | backend/logs/logout_debug.log |
| M | backend/logs/system.jsonl |
| M | backend/routes/admin.php |
| M | backend/routes/doctor.php |
| M | backend/routes/nurse.php |
| M | backend/routes/pharmacy.php |
| M | backend/routes/reception.php |
| M | backend/routes/users.php |
| A | backend/seed_demo_data.php |
| M | backend/utils/DbSchema.php |
| A | backend/utils/UsernameStrategy.php |
| M | frontend/assets/css/theme.css |
| M | frontend/assets/js/app.js |
| M | frontend/index.html |
| M | frontend/pages/admin/dashboard.html |
| M | frontend/pages/auth/login.html |
| M | frontend/pages/doctor/dashboard.html |
| M | frontend/pages/it/itdashboard.html |
| M | frontend/pages/nurse/dashboard.html |
| M | frontend/pages/nurse_aid/dashboard.html |
| M | frontend/pages/pharmacy/dashboard.html |
| M | frontend/pages/reception/dashboard.html |

## Untracked Files

- frontend/assets/css/sports-academy-theme.css
- frontend/assets/img/academy.jpg
- frontend/pages/template/launchpad.html

## Per-File Diff Stats

| Added | Deleted | File |
|---:|---:|---|
| 95 | 0 | CHANGELOG_2026-02-23_addendum.html |
| 74 | 0 | CHANGELOG_2026-02-23_addendum.md |
| 93 | 0 | CHANGELOG_2026-02-23_addendum.pdf |
| 61 | 0 | CHANGELOG_2026-02-25_addendum.html |
| 47 | 0 | CHANGELOG_2026-02-25_addendum.md |
| - | - | CHANGELOG_2026-02-25_addendum.odf |
| - | - | CHANGELOG_2026-02-25_addendum.odt |
| 157 | 0 | backend/logs/auth_debug.log |
| 3173 | 0 | backend/logs/logout_debug.log |
| 160 | 0 | backend/logs/system.jsonl |
| 30 | 13 | backend/routes/admin.php |
| 37 | 13 | backend/routes/doctor.php |
| 216 | 0 | backend/routes/nurse.php |
| 9 | 9 | backend/routes/pharmacy.php |
| 668 | 40 | backend/routes/reception.php |
| 16 | 15 | backend/routes/users.php |
| 239 | 0 | backend/seed_demo_data.php |
| 235 | 0 | backend/utils/DbSchema.php |
| 59 | 0 | backend/utils/UsernameStrategy.php |
| 163 | 0 | frontend/assets/css/theme.css |
| 104 | 12 | frontend/assets/js/app.js |
| 12 | 3 | frontend/index.html |
| 77 | 8 | frontend/pages/admin/dashboard.html |
| 177 | 256 | frontend/pages/auth/login.html |
| 42 | 14 | frontend/pages/doctor/dashboard.html |
| 1 | 3 | frontend/pages/it/itdashboard.html |
| 287 | 5 | frontend/pages/nurse/dashboard.html |
| 1 | 1 | frontend/pages/nurse_aid/dashboard.html |
| 106 | 7 | frontend/pages/pharmacy/dashboard.html |
| 751 | 169 | frontend/pages/reception/dashboard.html |

## Notes

- Backend log files are included because they are currently part of the local diff.
- PDF version of this changelog is generated as CHANGELOG_2026-03-09_addendum.pdf.
