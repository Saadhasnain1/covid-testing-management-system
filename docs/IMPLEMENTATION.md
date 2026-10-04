# Implementation and validation

## Phase 1 — Audit
Inspected all original PHP routes, includes, SQL dumps, upload files and bundled assets. Architecture, schema, workflows and defects are recorded in AUDIT.md. Original source/dumps were archived before edits. Runtime claims were deferred until isolated tests.

## Phase 2 — Foundation
Changed includes/config.php; added config.example.php and bootstrap.php. Database settings are overridable, connection uses utf8mb4 and strict mysqli errors, sessions and redirects are centralized, errors are logged and rendered clearly. Authentication no longer uses the faulty strlen(boolean) check. Every existing route now uses the shared foundation through its controller.

## Phase 3 — CRUD and workflow
Added request-page.php, staff-page.php, detail-page.php, list-page.php, dashboard-page.php, search-page.php and live-page.php. Existing route files delegate to these controllers. Preserved patient requests, repeat requests, staff CRUD, assignment, progress, history, search/date reports and statistics. Added structured results and report corrections. Patient/test and progress/history writes are atomic. Deletes are restricted to staff without assignments. Duplicate sample collection routes share one implementation.

## Phase 4 — Security
Added auth-page.php, tracking-page.php and download-report.php. Prepared queries, server validation, escaping, CSRF, password hashing, regenerated sessions, inactivity expiry, POST-only deletes/logout and patient verification replace unsafe legacy behavior. PDF upload verifies extension/MIME/signature/size and checks write success. Direct access to storage, legacy reports, tools and includes is denied. Reference mismatches cannot modify a different test: order is derived from the locked test record. Public availability enumeration is retired with HTTP 410. Recovery uses a CLI administrator reset rather than discoverable identity data.

## Phase 5 — Public design
Redesigned index.php and patient/login/live-update pages using shared header.php, navbar.php, footer.php, css/app.css and js/app.js. Added hero, features, workflow, CTAs, responsive forms, feedback, private tracking and a progress timeline. Removed obsolete COVID informational claims from the landing page.

## Phase 6 — Admin design
Redesigned sidebar, dashboard, request lists/details, team management, search/date forms and account settings. Added current database counts, badges, server pagination/filtering, empty states and print support. Desktop/mobile layouts checked in Chrome. Existing page URLs remain usable; legacy debug URL redirects home.

## Phase 7 — Tests
tools/tests/workflow.mjs performs 50 meaningful HTTP/database checks with synthetic records. The successful run covers first/repeat requests, malformed input, output escaping, injection login attempt, admin auth, CSRF, staff create/update/delete and assignment restrictions, all progress stages, skipped-stage rejection, delivery prerequisites, disguised PHP upload rejection, PDF creation/correction/download, patient identity enforcement, denied private paths, search/date filtering, not-found behavior and profile/password/logout. Database counts confirm atomic history relationships.

PHP 8.1.25 syntax checks pass. MariaDB 10.4.32 clean schema and both old dumps passed migration preflight/application/rerun in isolated databases. Desktop/mobile page widths and CSS/JS loads passed; screenshots were visually inspected. Additional targeted checks passed for MD5-on-login migration, session regeneration/cookie flags, 28 routes, record-derived references, pagination/page clamping, rejection of old GET deletion, and database-outage HTTP 503 behavior.

All 50 integration checks also passed through XAMPP Apache 2.4.58 with its PHP 8.1 module after cleanup. Apache denied direct configuration/tool/report/dotfile access. Testing used a custom configuration and loopback port, leaving the installed XAMPP configuration untouched. The minimal test configuration initially needed parent-directory permissions corrected; deployment checks also prompted an explicit dotfile denial rule in the application.

Testing initially failed because the sandbox denied XAMPP session writes; the authorized test server ran with access to the normal PHP temporary directories. One test assumed gapless staff IDs; that test was corrected to read the created record's ID. These were test-environment/test-assumption failures.

## Phase 8 — Repository preparation
Added README.md, .gitignore, database/covidtmsdb.sql, CLI tools and actual screenshots. Removed unused SB Admin styles/scripts, DataTables, chart demos, Font Awesome, easing, old informational images, SCSS and Gulp tooling after routes no longer referenced them. Bootstrap/jQuery distributions remain bundled with upstream notices.

Both seeded SQL dumps and historical report PDFs were removed from publishable paths after preserving originals locally; old PDFs are in ignored .local-backup/reportfiles. Uploaded and synthetic PDFs remain ignored. No working user database was changed.

## Remaining scope and limits
Local users must import the clean schema/create an administrator, or explicitly back up and migrate their existing database. No GitHub remote/push or public deployment was requested or performed. Session-based attempt limits and reference/mobile/birth-date verification are suitable demonstration mechanisms; verified patient accounts and shared abuse limits remain future improvements. Only PHP 8.1.25/MariaDB 10.4.32 were executed; no separate PHP 8.2+ or MySQL 8 service was available.
