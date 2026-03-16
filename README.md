# Hospital Management System (HMS)

A role-based hospital operations system covering reception, doctors, nurses, nurse aides, pharmacy, admin, and IT support workflows with realtime updates.

## Modules / Roles
- Admin
- Doctor
- Nurse
- Nurse Aid
- Reception
- Pharmacy (incl. Senior Pharmacist)
- IT Support

## Tech Stack
- PHP (backend API)
- MySQL (data store)
- HTML/CSS/JS + Bootstrap (frontend)
- Node.js + ws (realtime server)

## Requirements
- XAMPP (Apache + PHP + MySQL)
- Node.js 18+ (for realtime server)

## Quick Start
1. **Start XAMPP**
   - Start Apache and MySQL.
   - Ensure the project is placed under `C:\xampp\htdocs\Hospital_Management_System`.

2. **Database setup**
   - Create a database named `hospital_db`.
   - Import schema from:
     - `backend/config/database/hospital_db.sql`
   - Verify DB credentials in `backend/config/database.php`.

3. **JWT config**
   - Update secret and domain in `backend/config/jwt.php`.

4. **Realtime server**
   ```bash
   cd realtime
   npm install
   node server.js
   ```
   Default port is `8090`. The frontend connects via `WS_URL` in `frontend/assets/js/app.js`.

5. **Open the app**
   - `frontend/pages/auth/login.html`

## Frontend Fonts
Fonts are self-hosted under:
- `frontend/assets/css/fonts.css`
- `frontend/assets/fonts/*`

## Monitoring & Alerts
- Health endpoint: `backend/index.php/health` (or `/health`)
- Response includes DB and realtime server status.
- Simple watcher:
  ```powershell
  powershell -File scripts\monitor_watch.ps1 -BaseUrl "http://localhost/Hospital_Management_System/backend/index.php" -Beep
  ```
- For production, wire `/health` to your monitoring system (UptimeRobot, Prometheus, Zabbix, etc.).

## Email Reminders (Pending/Standby Work)
- Manual trigger via admin API:
  - `POST /backend/index.php/admin/send_reminders`
  - Optional JSON flags: `dry_run`, `force`, `include_internal`
- CLI trigger:
  ```bash
  php backend/send_reminders.php
  php backend/send_reminders.php --dry-run
  php backend/send_reminders.php --force --emails="a@gmail.com,b@gmail.com"
  ```
- PowerShell helper:
  ```powershell
  powershell -File scripts\send_reminders.ps1
  powershell -File scripts\send_reminders.ps1 -DryRun
  powershell -File scripts\send_reminders.ps1 -Force -Emails "a@gmail.com,b@gmail.com"
  ```
- Recommended automation (Windows Task Scheduler):
  - Program/script: `php`
  - Arguments: `C:\xampp\htdocs\Hospital_Management_System\backend\send_reminders.php`
  - Schedule: every 1-4 hours (or daily, based on your workflow)

Note: account creation now expects staff emails to be explicitly entered.
To actually deliver emails (including Gmail), configure SMTP environment variables for PHP/Apache:
- `HMS_SMTP_HOST` (e.g., `smtp.gmail.com`)
- `HMS_SMTP_PORT` (e.g., `587`)
- `HMS_SMTP_USER` (SMTP username/email)
- `HMS_SMTP_PASS` (SMTP app password)
- `HMS_SMTP_SECURE` (`tls` or `ssl`)
- `HMS_SMTP_FROM` (sender email)
- `HMS_SMTP_FROM_NAME` (sender display name)

## Automated Tests (Basic)
- Run the basic suite:
  ```powershell
  powershell -File scripts\run_tests.ps1 -BaseUrl "http://localhost/Hospital_Management_System/backend/index.php"
  ```
- Smoke only:
  ```powershell
  powershell -File scripts\smoke_test.ps1 -BaseUrl "http://localhost/Hospital_Management_System/backend/index.php"
  ```

## Release Checklist (Basic)
- [ ] **Secrets & config**
  - Set a strong JWT secret in `backend/config/jwt.php`.
  - Update JWT issuer/audience to production domain.
  - Move DB credentials out of code (env or secret manager).
- [ ] **Remove debug utilities**
  - Remove/secure: `backend/install.php`, `backend/db_direct_connect.php`, `backend/db_list_servers.php`, `backend/db_connection_test.php`.
- [ ] **Remove logs & sensitive artifacts**
  - Delete: `backend/logs/*`, `backend/logs/*.jsonl`, `backend/logs/*.txt`.
- [ ] **Clean repo artifacts**
  - Remove `realtime/node_modules` from version control.
  - Remove stray files like `frontend/assets/js/New Text Document.txt`.
- [ ] **.gitignore**
  - Add root `.gitignore` to exclude logs, node_modules, .env, tmp files.
- [ ] **Environment validation**
  - Verify `CONFIG.BASE_URL` and `WS_URL` resolve correctly in production.
- [ ] **Role & permissions audit**
  - Confirm role enums in DB and frontend match.
- [ ] **Smoke tests**
  - Run `scripts/smoke_test.ps1` and verify all role dashboards load.
- [ ] **Cross-device review**
  - Validate desktop, tablet, and mobile layouts.

## Notes
- The system uses banner notifications for UI alerts.
- For production, restrict direct access to backend directories.
