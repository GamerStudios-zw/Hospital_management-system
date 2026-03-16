# Weekly Changelog - 2026-03-09 to 2026-03-15

Generated: 2026-03-16 12:30:41 +02:00
Branch: Tony-dev
Commit window: 2 commits
Reporting scope: local git history from 2026-03-09 00:00 through 2026-03-15 23:59:59

## Source Note

- This weekly changelog is based on local repository history for commit `45ee5a6` on 2026-03-09 and commit `5e07fc2` on 2026-03-12.
- The March 9 workflow summary is cross-checked against the existing `CHANGELOG_2026-03-09_addendum.md` file in the project root.
- Where commit messages were brief, bug statements below are inferred from the changed route logic, status handling, and dashboard behavior in the diff.

## Executive Summary

- Strengthened reception registration and medical-aid capture workflows.
- Expanded doctor, nurse, and pharmacy coordination across consultation, prescribing, and admission handoff.
- Split injectable medication handling from normal pharmacy pickup so Nurse In Charge can administer injections directly.
- Tightened validation, queue-state handling, and patient handoff consistency across the weekly work.

## Fixes Delivered

### Reception and registration workflow

- Made the Register New Patient flow scrollable so longer capture sessions no longer break the form layout.
- Fixed medical-aid card visibility and enablement so it responds correctly to the selected registration type.
- Added medical-aid provider and cover-plan selection fields and synced member name from the patient full name.
- Removed the `Other` gender option to align frontend capture with backend validation.
- Added staff and student identity defaults plus faculty, programme, level, and semester capture fields.
- Relaxed national ID validation to allow a 6-8 digit middle segment.
- Improved manual date entry parsing and formatting.
- Locked reception queue interactions for protected states such as checked-in, with doctor, and completed.
- Added schema ensure logic and seed-data updates to support the expanded reception capture fields.

### Doctor, nurse, and pharmacy workflow

- Routed in-stock injectable prescriptions to Nurse In Charge using `nurse_admin_pending` instead of treating them as standard pharmacy pickup.
- Kept non-injection and external medications in the pharmacy workflow.
- Added dosage normalization for shorthand instructions such as `inj IV q8h x3`.
- Enforced injection-order validation so route and schedule are both required before submission.
- Returned routing counts from consultation completion so the UI can confirm what was sent to nurse, pharmacy, or external medication flow.
- Updated doctor waiting-list filtering so work stays scoped to the assigned clinician when applicable.

### Nurse operations and queue handling

- Added a Nurse In Charge injection administration queue.
- Added nurse-side injection administration recording, stock deduction, audit logging, and realtime events.
- Added a richer nurse history view for recent vitals, visits, and prescriptions.
- Updated admission handoff so patients move to `Ready for Admission` only after all medication tasks are cleared.
- Updated nurse-facing wording so discharge messaging points to Nurse In Charge instead of a doctor-only path.

### Pharmacy handling

- Made pending-pharmacy queries resilient when queue linkage or queue status changes.
- Normalized pharmacy and prescription status comparisons to lowercase so `pending`, `external`, and `dispensed` are handled consistently.
- Improved medication-name fallback logic so manual and external items still appear correctly in pharmacy and claim views.

### Additional workflow updates

- Added external referral creation and register views in the doctor workflow.
- Extended some doctor workflow access to `nurse_in_charge` where the updated process requires it.
- Refreshed dashboard search behavior so nurse workflow tabs search the correct tables and lists.

## Bugs Tackled Last Week

- Bug: Injectable medications were mixed into the normal pharmacy pickup path.
- Fix: Injectable in-stock orders now route to Nurse In Charge and remain separately trackable until administration.

- Bug: Prescription and pharmacy status handling used inconsistent casing such as `Pending`, `External`, and `Dispensed`, which could hide records or block downstream transitions.
- Fix: Status checks and writes were normalized with lowercase comparisons and updates.

- Bug: Queue records could fail to move to `Ready for Admission` when medication actions completed across different workflow branches.
- Fix: Remaining-medication checks now account for pharmacy pickup, external medication handling, and nurse-administered injections together.

- Bug: Manual or external medication names could disappear in pharmacy views that depended only on medicine joins.
- Fix: Pharmacy lookups now fall back to `medication_name` and notes-based values.

- Bug: Reception capture had frontend and backend mismatches around gender values, medical-aid capture, national ID formats, and manual date entry.
- Fix: Validation and schema support were aligned across the registration workflow.

- Bug: Reception staff could still interact with queue items already in protected lifecycle states.
- Fix: Protected-status interaction locks were added to the reception workflow.

- Bug: Nurse discharge wording still implied a doctor-only discharge command.
- Fix: The message now points to Nurse In Charge discharge flow.

## Included Commits

1. `45ee5a6` on 2026-03-09: `feat: update HMS workflows and add changelog addendum`
2. `5e07fc2` on 2026-03-12: `Route injection meds to nurse and keep pharmacy pickup separate`

## Areas Most Affected

- `backend/routes/reception.php`
- `backend/utils/DbSchema.php`
- `backend/seed_demo_data.php`
- `backend/routes/doctor.php`
- `backend/routes/nurse.php`
- `backend/routes/pharmacy.php`
- `frontend/pages/reception/dashboard.html`
- `frontend/pages/doctor/dashboard.html`
- `frontend/pages/nurse/dashboard.html`

## Closing Note

- This document covers work completed during the week of 2026-03-09 through 2026-03-15.
- The bug list is written to be readable by project stakeholders, but it stays grounded in the local git history and diff evidence available in this repository.
