# School consultation workflow

## Apply the schema

Run `php artisan migrate` on an existing installation. This adds school catalogs,
class assignments, teacher preferences/approval, queue privacy, and availability
cancellation without deleting existing users or bookings. Back up a deployed
database before running migrations. Existing teacher profiles default to **not
approved**; an admin must grant consultation permission. Existing student profiles
can be assigned a grade and section through Accounts.

## School setup (admin)

1. Open **School lists**. Create grade levels, sections under each grade, and subjects.
   New choices immediately appear during registration. Names can be edited, but
   options referenced by profiles or sections cannot be deleted.
2. Open **Accounts → Edit** to correct names/emails and the separate school profile.
   Student profiles include class, LRN, contact, birthday, sex, address, and status.
   Teacher profiles include subjects and consultation permission.
3. Approve a teacher's **Consultation permission** to allow publishing availability
   and confirming requests. This is a teacher-level permission, not per-schedule
   approval. Revocation blocks new publication, booking, and confirmation, but
   preserves existing bookings; teachers can still reject/cancel them.
4. Under **Settings**, choose queue visibility: teacher choice, hide all, or show all.
   School-wide rules override teacher preferences. Teachers and authorized admins
   can always see student names; other students see names only when permitted.
   Consultation reasons are never shown to other students.
5. **Consultation monitoring** is read-only. Admin cannot approve student bookings.

School lists use the existing `settings` view/edit permissions. Editing school
profiles uses `accounts` edit permission and account hierarchy checks. Monitoring
uses `dashboard` view permission.

## Teacher workflow

- The teacher portal opens with **My schedule calendar**. Browse previous/next
  months, return to Today, and select a date to see availability and consultations
  in time order, including student names, reasons, locations, and booking status.
  Past schedules are available by month; cancelled availability and
  cancelled/rejected bookings are excluded from the scheduled calendar.
- Calendar markers distinguish blue published availability, amber pending
  requests, green confirmed bookings, and gray completed consultations. Desktop
  cells also show counts and status-colored previews. A rose circle marks Today,
  and a rose outline marks the selected date. The legend and date tooltips explain
  every marker; empty months explain why no date indicators are displayed.
- Register with one or more admin-defined subjects; approval is initially off.
- Set default duration (5–240 minutes), automatic/manual booking approval, and
  student-name visibility in **Consultation settings**.
- Once approved, publish a future date, start/end time, location, and slot duration.
  Availability must contain complete slots and must not overlap another active
  window. Each window retains its duration even if defaults change later.
- Manual bookings reserve slots as pending; automatic bookings are confirmed.
  Changing approval preference affects new bookings only.
- Review the time-ordered queue and confirm/reject requests, complete confirmed
  consultations, or cancel a confirmed booking.
- Cancelling an availability window automatically **rejects all pending and
  confirmed bookings** in it, removes it from booking calendars, and preserves
  history. Completed/cancelled/rejected records are unchanged.

## Student workflow

- Register with a grade and a section belonging to that grade.
- Browse approved teachers, their subjects, monthly calendar, available slots,
  and time-ordered queues. Enter a required consultation reason when booking.
- Slots must be future, aligned to the teacher's duration, and within availability.
  The app checks teacher and student appointment conflicts inside transactions.
- View pending/confirmed bookings and booking history in the dashboard.
- Cancel an upcoming pending/confirmed booking to free its slot. To change time,
  cancel and book again; there is no reschedule action.
- Automatically rejected bookings appear in history with an availability
  cancellation explanation. Updates appear on page refresh; no email/SMS
  notification service is configured.

All times use `config('app.timezone')` (currently configured in `config/app.php`).
Set the application's timezone appropriately before using schedules at school.

## Optional local demo data

Run `php artisan db:seed --class=DemoConsultationSeeder` to add fictional demo
accounts, catalogs, three days of future availability, queues, and a cancelled
session. This seeder refuses to run in production. It preserves existing accounts
and school data; rerunning does not duplicate schedules or reset edited demo
accounts. Existing schedules are not refreshed to later dates on rerun.

New accounts all use the local testing password `DemoConsult!2026`:

- Admin: `demo.admin@example.com` at `/admin/login`.
- Teachers: `demo.teacher1@example.com` (manual approval, names hidden),
  `demo.teacher2@example.com` (auto-approval, names shown), and
  `demo.teacher3@example.com` (awaiting admin approval).
- Students: `demo.student1@example.com` through `demo.student4@example.com`.
- Teachers and students sign in at `/portal/login`.

Do not use these publicly documented demo credentials in a deployed school app.

## Automated checks

### Mobile navigation and responsive layouts

Below 1024px, signed-in students, teachers, and administrators use fixed bottom
navigation. Desktop layouts retain the student/admin sidebars. The teacher portal
uses a fixed desktop sidebar and a full-width workspace (no centered width cap),
so the calendar expands to fill the screen beside navigation. Teacher sidebar
links retain the currently selected month and date.
Bottom spacing includes the phone safe-area inset, so the bar does not cover page
content. Admin destinations are filtered by module permissions; Accounts retains
an accessible Roles link. Teacher tabs navigate to calendar, requests, publishing
(approved teachers only), and preferences, and highlight the section in view.

An optional real-browser check is available at `tests/browser/mobile-responsive.cjs`.
With Playwright installed, demo accounts seeded, and the app served at
`http://127.0.0.1:8091`, run `node tests/browser/mobile-responsive.cjs`.
Set `APP_TEST_URL` for another local URL, `PLAYWRIGHT_MODULE` for an external
Playwright installation, or `CHROME_PATH` to use an installed Chrome executable.
It checks teacher, student, and admin layouts at 320, 375, 390, 640, 768, 1024,
1280, 1440, 1920, and 2560px, including long teacher content, touch targets,
navigation, overflow, and full-width desktop sidebar sizing.

The responsive calendar follows the **compact month + readable agenda** pattern
illustrated by [Mobiscroll's mobile month view](https://demo.mobiscroll.com/javascript/eventcalendar/mobile-month-view)
and [FullCalendar's list view](https://fullcalendar.io/docs/list-view). This is an
original implementation, not copied vendor source, and adds no calendar library
dependency. Small calendar containers show date numbers and event dots, with
details in the agenda below; larger containers add event previews, and the
teacher's agenda moves alongside the month when there is enough space. CSS
container queries adapt to actual calendar space, including the student sidebar,
rather than assuming viewport width equals available width. Months render only
the four, five, or six rows required. All date navigation still works without JS.

Run `php artisan test`. Workflow tests cover registration catalogs, teacher
permission, teacher-only booking approval, duration snapshots, privacy overrides,
queue order, cancellation/rebooking, history, invalid slots, and conflicts.
Run `node --test tests/js/registration.test.cjs` for registration step validation.
