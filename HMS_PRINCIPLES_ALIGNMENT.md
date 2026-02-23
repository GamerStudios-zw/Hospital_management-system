# HMS Principles Alignment

This checklist maps core Hospital Management System principles to this codebase and highlights concrete controls.

## 1) End-to-end clinical/operational dataflow
- Status: Partially aligned
- Evidence:
  - Role-separated modules and routes exist for reception/doctor/nurse/pharmacy/admin/IT.
  - Pharmacy flow ties prescription status, stock movement, and queue state transitions.
- Next actions:
  - Define a canonical encounter lifecycle (`registered -> triaged -> consulted -> dispensed -> admitted/discharged`) and enforce allowed transitions centrally.

## 2) Interoperability-ready boundaries
- Status: Partially aligned
- Evidence:
  - Modular route structure and JSON API responses across modules.
  - Realtime event emission for key updates (`Realtime::emit`).
- Next actions:
  - Add explicit integration contracts (event catalog + payload schema versioning).
  - Add import/export mapping for external standards (FHIR/HL7 bridge layer) where needed.

## 3) Security and access control (least privilege)
- Status: Improved
- Evidence:
  - JWT auth + role checks via `AuthMiddleware` and `RoleMiddleware`.
  - CORS now origin-scoped with configurable allowlist in `backend/index.php`.
  - Controlled-substance dispense path now enforces approval for non-senior roles.
- Next actions:
  - Move JWT secret and DB credentials to environment-only config.
  - Add rate limiting on auth and sensitive write endpoints.

## 4) Auditability and traceability
- Status: Improved
- Evidence:
  - Request correlation id is propagated through `X-Request-ID`.
  - Structured activity/audit logging exists (`ActivityLogger`) with actor/entity/source.
  - High-risk pharmacy write operations now write consistent audit events.
- Next actions:
  - Enforce audit logging policy for all non-pharmacy write endpoints.
  - Add a periodic integrity check for missing audit events on critical transactions.

## 5) Data integrity and safe transactions
- Status: Improved
- Evidence:
  - Dispense and PO creation paths use transaction boundaries.
  - SQL injection risk removed in `pharmacy/interactions_check` via prepared statements.
  - Server-side actor attribution now replaces client-supplied actor IDs in key workflows.
- Next actions:
  - Add stricter status enums/validation across all workflow endpoints.
  - Add DB foreign keys and constraints review for all workflow tables.

## 6) Operational resilience
- Status: Partially aligned
- Evidence:
  - Health route and maintenance mode gate are present.
  - Log rotation exists for file logs.
- Next actions:
  - Add backup/restore drills and runbook checks.
  - Add alerting thresholds tied to auth failures, queue backlog, and API errors.
