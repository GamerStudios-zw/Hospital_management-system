# Hospital Management System (HMS)
## System-Wide Overview and Technology Stack

Document Date: March 12, 2026  
Repository: `C:\xampp\htdocs\Hospital_Management_System`

## 1. Executive Summary
The Hospital Management System (HMS) is a role-based web platform that manages end-to-end hospital operations, including patient registration, triage, consultation, admission flow, pharmacy dispensing, discharge, administration, and IT support ticketing.

The system is built as a modular PHP + MySQL backend with role-specific frontend dashboards and a Node.js WebSocket sidecar for realtime event propagation.

## 2. System Scope and Primary Objectives
- Centralize patient operations across departments.
- Enforce role-based access and workflow boundaries.
- Provide realtime or near-realtime visibility for operational teams.
- Maintain auditable clinical and administrative actions.
- Support day-to-day execution for all core hospital roles from a single system.

## 3. Core User Roles and Operational Responsibilities
- `admin`: user governance, assignments, global controls, system oversight.
- `receptionist`: patient registration, queue placement, appointment scheduling, identity capture.
- `nurse_aid`: triage queue handling, vital capture in triage, daily vitals views, discharge read-only review.
- `nurse` (nurse in ward context): ward/bed operations, urgent-care coordination, admission waiting, escalations, handover.
- `doctor` / `nurse_in_charge`: consultation, prescriptions, escalations, referrals, discharge summaries.
- `pharmacist` / `senior_pharmacist`: dispensing, inventory movement, pharmacy workflows.
- `it_support`: ticketing, system support operations, monitoring support.

## 4. End-to-End Functional Flow
1. Reception registers or locates patient records.
2. Reception places patient in active workflow/queue and handles appointment actions.
3. Nurse Aid performs triage capture and forwards clinical state.
4. Nurse In Charge (doctor module) performs consultation and writes prescriptions or referrals.
5. Pharmacy receives pending medication work, dispenses, and records stock movement.
6. Nurse workflows manage ward admission, handover, and escalation loops.
7. Discharge summaries are produced and tracked.
8. Admin and IT observe activity, health, and incidents.

## 5. Architecture Overview
HMS follows a modular monolith backend plus realtime sidecar:

- Frontend (static pages + JS):
  - role dashboards under `frontend/pages/*/dashboard.html`
  - shared API/realtime logic in `frontend/assets/js/app.js`

- Backend API:
  - single entry router: `backend/index.php`
  - module routes in `backend/routes/*.php`
  - middleware-based authentication and authorization

- Database:
  - MySQL database `hospital_db`
  - base schema + runtime schema ensure helpers

- Realtime sidecar:
  - Node.js WebSocket service in `realtime/server.js`
  - backend emits events to sidecar via HTTP `/emit`

## 6. Backend Module Map
Router modules dispatched by `backend/index.php`:
- `auth`
- `admin`
- `users`
- `reception`
- `doctor`
- `patients`
- `nurse`
- `nurse_aid`
- `logs`
- `pharmacy`
- `inventory`
- `reports`
- `shifts`
- `settings`
- `it`
- `health`

This modular breakdown separates responsibilities while keeping deployment simple.

## 7. Technology Stack
### 7.1 Backend
- PHP (XAMPP runtime)
- Composer dependency:
  - `firebase/php-jwt` `^7.0`
- REST-style JSON endpoints via route modules
- Middleware:
  - `AuthMiddleware`
  - `RoleMiddleware`

### 7.2 Frontend
- HTML5, CSS3, JavaScript (vanilla)
- Bootstrap `5.3.3`
- Bootstrap Icons `1.11.x`
- Chart.js `4.4.1`
- Browser storage:
  - JWT and user context in `localStorage`

### 7.3 Realtime Layer
- Node.js (project requirement: 18+)
- Package:
  - `ws` `^8.17.0`
- Realtime endpoint (default):
  - WebSocket/HTTP server on port `8090`
- Integration:
  - backend emits events using `backend/utils/Realtime.php`

### 7.4 Database
- MySQL (InnoDB, utf8mb4)
- Baseline SQL:
  - `backend/config/database/hospital_db.sql`
- Runtime schema extension helpers:
  - `backend/utils/DbSchema.php`

## 8. Security and Access Control Model
- JWT-based authentication for API access.
- Role checks performed at route layer.
- Request identity correlation via `X-Request-ID`.
- Maintenance mode gate in backend router with admin bypass policy.
- Scoped CORS allowlist behavior in backend front controller.

## 9. Data and Domain Areas
Major data domains include:
- Users and sessions
- Patients and identity extensions
- Queue/visits/appointments
- Vitals and triage history
- Prescriptions and pharmacy operations
- Bed and ward management
- Escalations, handovers, discharge summaries
- Activity/audit logs
- IT tickets

## 10. Realtime and Synchronization Behavior
- Dashboards connect to:
  - `ws://<host>:8090`
- Backend publishes operational events to sidecar.
- Frontends combine:
  - websocket-driven updates
  - periodic data refresh polling
  - local tab synchronization logic

This gives practical operational responsiveness without requiring a full SPA framework.

## 11. Deployment and Runtime Requirements
- XAMPP (Apache + PHP + MySQL)
- Node.js for realtime service
- Project root:
  - `C:\xampp\htdocs\Hospital_Management_System`

Recommended startup order:
1. Start Apache + MySQL in XAMPP.
2. Ensure database schema is imported and accessible.
3. Validate DB and JWT config files.
4. Start realtime sidecar:
   - `cd realtime`
   - `npm install`
   - `node server.js`
5. Open login page:
   - `frontend/pages/auth/login.html`

## 12. Observability, Monitoring, and Quality Controls
- Health endpoint:
  - `/backend/index.php/health` (or `/health` route path)
- Operations scripts:
  - `scripts/monitor_watch.ps1`
  - `scripts/run_tests.ps1`
  - `scripts/smoke_test.ps1`
- Activity logging and audit records through utility layer.

## 13. Strengths and Practical Tradeoffs
Strengths:
- Clear role-driven module design.
- Fast local deployment on standard XAMPP stack.
- Realtime operational awareness with simple sidecar architecture.
- Broad domain coverage across clinical and administrative operations.

Tradeoffs:
- Monolithic routing can become harder to govern as features scale.
- Runtime schema ensure patterns require strict release discipline.
- Realtime sidecar should be network-restricted and hardened in production.

## 14. Suggested Next Technical Improvements
1. Introduce strict versioned database migrations as deployment gates.
2. Publish an OpenAPI contract for core modules.
3. Expand automated test coverage per role workflow.
4. Move all secrets/config to environment-based configuration.
5. Add centralized log shipping and retention policies.

## 15. Conclusion
HMS is a complete, role-based hospital operations platform with a practical technology stack:
- PHP + MySQL for core business operations
- Bootstrap/vanilla frontend dashboards for each role
- Node.js WebSocket sidecar for realtime coordination

It is well positioned for continued growth, with clear opportunities for stronger deployment governance, API contracts, and observability standardization.

