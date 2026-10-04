# Original application audit
Audited October 3, 2026. PHP 8.1.25 and MariaDB 10.4 tools are installed through XAMPP. No Git repository or database listener was present. Existing features are identified by source inspection, not verified runtime behavior.

## Architecture and page inventory
Procedural PHP/mysqli with Bootstrap 4, jQuery, SB Admin 2, DataTables, Font Awesome, shared includes and SCSS/Gulp assets.

| Pages | Purpose |
| --- | --- |
| index.php | Landing page |
| new-user-testing.php, registered-user-testing.php | New and repeat patient test requests |
| live-test-updates.php | Aggregate requests by state |
| patient-search-report.php, patient-report.php, patient-report-details.php | Public report lookup |
| login.php, logout.php, password-recovery.php | Admin authentication and insecure mobile-based reset |
| dashboard.php | Database-driven statistics |
| all-test.php, new-test.php, assigned-test.php, ontheway-samplecollection-test.php, sample-collected-test.php, sample-collected-tests.php, samplesent-lab-test.php, reportdelivered-test.php | Lists by workflow stage; sample-collected-tests is a duplicate |
| test-details.php | Assignment, progress history, report upload |
| add-phlebotomist.php, manage-phlebotomist.php, edit-phlebotomist.php | Staff CRUD |
| search-report.php, search-report-result.php | Admin search |
| bwdates-report-ds.php, bwdates-report-result.php | Date reports; selection contains unrelated copied insertion code |
| profile.php, change-password.php | Admin account |
| check_availability.php | Availability AJAX |
| test.php | Debug output |

Includes hard-code database settings, assume sessions exist, output unescaped names, and reference a missing profile image. The application manages COVID testing despite the vaccine-management name.

## Schema
Two conflicting seeded dumps (2021 and 2023) share five tables: tbladmin, tblpatients, tblphlebotomist, tbltestrecord, tblreporttracking. Only primary keys exist; no unique identifiers or foreign keys. Phone numbers are numeric, encoding is latin1, dates are inconsistent strings. Patients join tests by phone; tracking joins tests by order; assignment stores employee ID and name. New status is NULL; subsequent statuses are Assigned, On the Way for Collection, Sample Collected, Sent to Lab, Delivered. There is no structured test result field.

## Problems
SQL interpolation across CRUD/search/auth; MD5 passwords; recovery based on discoverable identity; faulty strlen(boolean) auth; undefined POST/session values; no CSRF or regeneration; GET deletion; unsafe public record access/direct PDFs; extension-only uploads; unchecked upload moves; non-atomic request/history writes; random non-unique orders; error suppression; script redirects; dead copied code. UI repeats templates, mixes public/admin navigation, has weak validation/empty states and dated styling. Historical dumps and PDFs contain patient-like records and are unsuitable public fixtures.

## Plan
1. Repair configuration, sessions, errors and redirect handling.
2. Preserve routes/table names while consolidating repeated query/controller logic.
3. Secure CRUD, transactions, authentication, tracking and upload/download authorization.
4. Introduce shared Bootstrap public/admin presentation.
5. Supply clean schema, explicit migration and CLI administrator tools.
6. Verify in an isolated database, lint, audit links.
7. Document setup and phases; exclude local data.

Original PHP/includes/dumps are preserved locally in ignored .local-backup/original-source.zip.
