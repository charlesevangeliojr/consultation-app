# Demo credentials and database data

These are **fictional, local-development accounts**. Their credentials are public
because this document and the SQL file are intended to be committed to GitHub.
Do not use these accounts or passwords in a deployed school system.

## Login credentials

For an explanation of the features available to each account, see the
[App features and user guide](app-features.md).

**Password for all eight demo accounts:** `DemoConsult!2026`

| Role | Name | Sign-in identifier | Login page |
| --- | --- | --- | --- |
| Admin | Demo School Administrator | `demo.admin@example.com` | `/admin/login` |
| Teacher | Maria Santos | `demo.teacher1@example.com` | `/portal/login` |
| Teacher | Daniel Reyes | `demo.teacher2@example.com` | `/portal/login` |
| Teacher | Angela Cruz | `demo.teacher3@example.com` | `/portal/login` |
| Student | Alex Mendoza | LRN `990000000001` | `/portal/login` |
| Student | Bea Garcia | LRN `990000000002` | `/portal/login` |
| Student | Carlo Ramos | LRN `990000000003` | `/portal/login` |
| Student | Diana Flores | LRN `990000000004` | `/portal/login` |

Students use **LRN + password**, not their stored demo email, to sign in. The demo
student records still have emails `demo.student1@example.com` through
`demo.student4@example.com` for sample account identification. Teachers and admins
use the email identifiers in the table.

Maria is approved, handles Mathematics and Science, manually
approves requests, and hides queue names. Daniel is approved, handles English and
Filipino, automatically confirms bookings, and shows queue
names. Angela handles Computer Studies and awaits admin consultation approval.
School-wide privacy settings still override teacher preferences.
New requests use student-selected start/end times, with a default maximum of
30 minutes per consultation. Teachers can switch the maximum to one hour in
Consultation settings. Historical seed bookings retain their original durations
(including Daniel's 20-minute examples); fixed slots no longer constrain new requests.

Teachers now have a required profile-setup step. After importing or seeding,
sign in as a demo teacher and complete the required personal details and upload
a JPG, PNG, or WebP profile picture (up to 2 MB) and describe the support offered
to students. There is no subject-selection step in the profile. Phone numbers use a country-code
dropdown; addresses use the same suggestion-assisted fields as student registration.
Use fictional details for these
demo accounts. The existing SQL snapshot does not include teacher contact details
or photos, so the setup page appears before the calendar. Completing a profile
does not change admin-managed consultation approval or existing subject assignments.
Teachers may update their consultation description without admin confirmation.

## Included sample data

- Grades 10, 11, and 12, with two sections each: Newton/Galileo,
  Einstein/Curie, and Tesla/Darwin.
- Five historical subjects and teacher subject assignments. These are retained
  sample data; subjects are no longer managed in admin or required at registration.
- Four student profiles with class assignments and fictional contact details.
- Six active availability windows on October 15–17, 2026.
- Four active bookings (pending/confirmed).
- One cancelled availability window with a rejected booking.

## Import the SQL snapshot

File: [`database/sql/demo-consultation-data.sql`](../database/sql/demo-consultation-data.sql).
It is a **MySQL/MariaDB data-only SQL file**, not a full database/schema dump.

1. Install the project dependencies and configure your own `.env` database settings.
2. Run migrations to create the tables:

   ```sh
   php artisan migrate
   ```

3. Import the SQL file into that same database using phpMyAdmin's **Import** tab,
   MySQL Workbench's SQL editor, or the MySQL CLI:

   ```sh
   mysql -u YOUR_DB_USER -p YOUR_DATABASE < database/sql/demo-consultation-data.sql
   ```

   The CLI example uses a shell supporting `<` input redirection, such as Bash
   or Windows Command Prompt (not PowerShell).

4. Sign in using the credentials above.

The SQL uses lookups rather than fixed primary-key IDs, preserves matching rows,
and contains hashed demo passwords. It does not include `.env` configuration,
real user accounts, sessions, tokens, or other students' consultation records.
Use a fresh **development** database for the most predictable import. Existing
accounts with matching emails are not overwritten, so their existing passwords
and preferences remain unchanged. Existing conflicting LRNs can cause import
failure. Back up any existing database before importing.

The snapshot has fixed dates; once those dates pass, those sessions cannot be
booked. For current future availability on a fresh installation, use the seeder
**instead of** importing the SQL:

```sh
php artisan db:seed --class=DemoConsultationSeeder
```

Do not run `migrate:fresh` on a database containing data you want to keep.

## Regenerate the demo SQL

After creating the demo accounts locally:

```sh
php artisan demo:export-sql
```

This overwrites only `database/sql/demo-consultation-data.sql`. The command
refuses to run in production or if the eight demo accounts do not have the
documented demo password. Review any edited demo profiles before committing.

## Include the data in GitHub

Commit this Markdown file, `database/sql/demo-consultation-data.sql`, the demo
seeder, and the exporter along with the application changes. The SQL file is
not ignored by the project's `.gitignore`.

Pushing to GitHub includes these files—it does **not** automatically import the
data on another computer. Each developer must migrate and import or run the seeder.
Never commit your `.env` or a full dump containing real school records.
