# Public GitHub publication manifest

Prepared October 4, 2026. No files have been staged, committed or pushed.

The PHP application now lives at the repository root. Application PHP/CSS/JavaScript and asset files were verified byte-for-byte against their pre-move versions. Only test paths, setup documentation and guards for the newly colocated database/documentation directories were adjusted. Apache's containing folder name continues to determine the URL prefix; route names, UI and database business logic are unchanged.

`.local-backup/` was already untracked (this repository has an empty index and no commits). It remains on disk and ignored. Useful tests from `.verification/` were moved into `tools/tests/`; old browser profiles, temporary databases, logs, copied private SQL dumps and one-off snapshot/screenshot scripts were removed. Generated test artifacts stay ignored in `.test-artifacts/`.

The canonical `database/covidtmsdb.sql` contains only five CREATE TABLE definitions and fresh-install database selection. It contains no inserted records, default accounts, password hashes, personal data or credential values, and no destructive statements. It is an installer for a new database, not a migration for a working database. `config.example.php` documents local defaults without deployment secrets.

Ignore checks passed for local configuration, environment files, credentials, keys, logs, sessions, uploaded/generated reports, private data, backup directories/archives and noncanonical database exports. Existing documentation screenshots contain synthetic demo records. Keep future real records and custom private-data exports in ignored private/report/storage directories.

The existing `C:\xampp\htdocs\covid-tms` and `covid-tms-modern` applications, their databases, and XAMPP/Apache/MySQL configuration were not changed. Tests ran only against a newly initialized disposable MariaDB instance on port 33316.

## Validation

- PHP lint: 53 files passed.
- Workflow: all 50 HTTP/database checks passed.
- Regression: MD5 upgrades, regenerated session/cookie flags, 28 routes, record-derived references, pagination/clamping and GET-delete rejection passed.
- Browser: 52 page/viewport combinations at 375, 768, 1366 and 1920px; 29 routes; assets/navigation, overflow, labels, keyboard table access, mobile navigation, reduced motion, report tracking/download and confirmation focus/Escape/POST behavior passed.
- Migration audit plus two application runs passed on the disposable test database.
- Publication-directory access guards, ignore rules, empty Git index and application byte comparisons passed.

Reproduce from the repository root with `node tools/tests/run.mjs` (Node.js 22+, XAMPP and Chrome). The first browser run stalled because service logging filled an unread process pipe; the harness now logs directly to ignored files, and the complete rerun passed.

## GitHub directory tree

Only publication candidates are shown; `.git/`, local backups, private runtime data and disposable test artifacts are excluded. Empty `img/` is not included because Git does not track empty directories.

```text
VMS Eproject final/
|-- .gitignore
|-- .htaccess
|-- README.md
|-- add-phlebotomist.php
|-- all-test.php
|-- assigned-test.php
|-- bwdates-report-ds.php
|-- bwdates-report-result.php
|-- change-password.php
|-- check_availability.php
|-- css/
|   `-- app.css
|-- dashboard.php
|-- database/
|   |-- .htaccess
|   `-- covidtmsdb.sql
|-- docs/
|   |-- .htaccess
|   |-- AUDIT.md
|   |-- IMPLEMENTATION.md
|   |-- PUBLICATION.md
|   |-- UI-POLISH.md
|   `-- screenshots/
|       |-- confirmation-desktop.png
|       |-- dashboard-desktop.png
|       |-- dashboard-mobile.png
|       |-- landing-desktop.png
|       |-- landing-full.png
|       |-- landing-mobile.png
|       |-- landing-wide.png
|       |-- login-desktop.png
|       |-- navigation-mobile.png
|       |-- request-desktop.png
|       |-- request-details-desktop.png
|       |-- request-mobile.png
|       |-- requests-mobile.png
|       `-- tracking-mobile.png
|-- download-report.php
|-- edit-phlebotomist.php
|-- includes/
|   |-- .htaccess
|   |-- auth-page.php
|   |-- bootstrap.php
|   |-- config.example.php
|   |-- config.php
|   |-- dashboard-page.php
|   |-- detail-page.php
|   |-- footer.php
|   |-- footer2.php
|   |-- header.php
|   |-- icons.php
|   |-- list-page.php
|   |-- live-page.php
|   |-- navbar.php
|   |-- request-page.php
|   |-- search-page.php
|   |-- sidebar.php
|   |-- staff-page.php
|   |-- topbar.php
|   `-- tracking-page.php
|-- index.php
|-- js/
|   `-- app.js
|-- live-test-updates.php
|-- login.php
|-- logout.php
|-- manage-phlebotomist.php
|-- new-test.php
|-- new-user-testing.php
|-- ontheway-samplecollection-test.php
|-- password-recovery.php
|-- patient-report-details.php
|-- patient-report.php
|-- patient-search-report.php
|-- profile.php
|-- registered-user-testing.php
|-- reportdelivered-test.php
|-- reportfiles/
|   |-- .gitkeep
|   `-- .htaccess
|-- sample-collected-test.php
|-- sample-collected-tests.php
|-- samplesent-lab-test.php
|-- search-report-result.php
|-- search-report.php
|-- storage/
|   |-- .gitkeep
|   `-- .htaccess
|-- test-details.php
|-- tools/
|   |-- .htaccess
|   |-- admin.php
|   |-- dev-router.php
|   |-- migrate.php
|   `-- tests/
|       |-- regression.mjs
|       |-- run.mjs
|       |-- ui-browser-check.mjs
|       `-- workflow.mjs
`-- vendor/
    |-- bootstrap/
    |   |-- css/
    |   |   |-- bootstrap-grid.css
    |   |   |-- bootstrap-grid.css.map
    |   |   |-- bootstrap-grid.min.css
    |   |   |-- bootstrap-grid.min.css.map
    |   |   |-- bootstrap-reboot.css
    |   |   |-- bootstrap-reboot.css.map
    |   |   |-- bootstrap-reboot.min.css
    |   |   |-- bootstrap-reboot.min.css.map
    |   |   |-- bootstrap.css
    |   |   |-- bootstrap.css.map
    |   |   |-- bootstrap.min.css
    |   |   `-- bootstrap.min.css.map
    |   `-- js/
    |       |-- bootstrap.bundle.js
    |       |-- bootstrap.bundle.js.map
    |       |-- bootstrap.bundle.min.js
    |       |-- bootstrap.bundle.min.js.map
    |       |-- bootstrap.js
    |       |-- bootstrap.js.map
    |       |-- bootstrap.min.js
    |       `-- bootstrap.min.js.map
    `-- jquery/
        |-- jquery.js
        |-- jquery.min.js
        |-- jquery.min.map
        |-- jquery.slim.js
        |-- jquery.slim.min.js
        `-- jquery.slim.min.map
```

## Exact publication file list

115 files would be included by `git add .` with the current ignore rules. Nothing is currently staged. This list includes this manifest itself.

```text
.gitignore
.htaccess
README.md
add-phlebotomist.php
all-test.php
assigned-test.php
bwdates-report-ds.php
bwdates-report-result.php
change-password.php
check_availability.php
css/app.css
dashboard.php
database/.htaccess
database/covidtmsdb.sql
docs/.htaccess
docs/AUDIT.md
docs/IMPLEMENTATION.md
docs/PUBLICATION.md
docs/UI-POLISH.md
docs/screenshots/confirmation-desktop.png
docs/screenshots/dashboard-desktop.png
docs/screenshots/dashboard-mobile.png
docs/screenshots/landing-desktop.png
docs/screenshots/landing-full.png
docs/screenshots/landing-mobile.png
docs/screenshots/landing-wide.png
docs/screenshots/login-desktop.png
docs/screenshots/navigation-mobile.png
docs/screenshots/request-desktop.png
docs/screenshots/request-details-desktop.png
docs/screenshots/request-mobile.png
docs/screenshots/requests-mobile.png
docs/screenshots/tracking-mobile.png
download-report.php
edit-phlebotomist.php
includes/.htaccess
includes/auth-page.php
includes/bootstrap.php
includes/config.example.php
includes/config.php
includes/dashboard-page.php
includes/detail-page.php
includes/footer.php
includes/footer2.php
includes/header.php
includes/icons.php
includes/list-page.php
includes/live-page.php
includes/navbar.php
includes/request-page.php
includes/search-page.php
includes/sidebar.php
includes/staff-page.php
includes/topbar.php
includes/tracking-page.php
index.php
js/app.js
live-test-updates.php
login.php
logout.php
manage-phlebotomist.php
new-test.php
new-user-testing.php
ontheway-samplecollection-test.php
password-recovery.php
patient-report-details.php
patient-report.php
patient-search-report.php
profile.php
registered-user-testing.php
reportdelivered-test.php
reportfiles/.gitkeep
reportfiles/.htaccess
sample-collected-test.php
sample-collected-tests.php
samplesent-lab-test.php
search-report-result.php
search-report.php
storage/.gitkeep
storage/.htaccess
test-details.php
tools/.htaccess
tools/admin.php
tools/dev-router.php
tools/migrate.php
tools/tests/regression.mjs
tools/tests/run.mjs
tools/tests/ui-browser-check.mjs
tools/tests/workflow.mjs
vendor/bootstrap/css/bootstrap-grid.css
vendor/bootstrap/css/bootstrap-grid.css.map
vendor/bootstrap/css/bootstrap-grid.min.css
vendor/bootstrap/css/bootstrap-grid.min.css.map
vendor/bootstrap/css/bootstrap-reboot.css
vendor/bootstrap/css/bootstrap-reboot.css.map
vendor/bootstrap/css/bootstrap-reboot.min.css
vendor/bootstrap/css/bootstrap-reboot.min.css.map
vendor/bootstrap/css/bootstrap.css
vendor/bootstrap/css/bootstrap.css.map
vendor/bootstrap/css/bootstrap.min.css
vendor/bootstrap/css/bootstrap.min.css.map
vendor/bootstrap/js/bootstrap.bundle.js
vendor/bootstrap/js/bootstrap.bundle.js.map
vendor/bootstrap/js/bootstrap.bundle.min.js
vendor/bootstrap/js/bootstrap.bundle.min.js.map
vendor/bootstrap/js/bootstrap.js
vendor/bootstrap/js/bootstrap.js.map
vendor/bootstrap/js/bootstrap.min.js
vendor/bootstrap/js/bootstrap.min.js.map
vendor/jquery/jquery.js
vendor/jquery/jquery.min.js
vendor/jquery/jquery.min.map
vendor/jquery/jquery.slim.js
vendor/jquery/jquery.slim.min.js
vendor/jquery/jquery.slim.min.map
```
