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

- Teachers must complete their profile before entering the workspace. Required:
  full name, valid email, contact number with a country/calling-code selection,
  a birthday before today, sex (Male/Female, matching student registration), a
  structured address, an initial profile photo, and a description of what the
  teacher offers students. Profile setup does not require selecting subjects.
  Existing teachers with missing details are redirected to My profile as well.
  A server-side check also blocks direct publishing, preference changes, and
  booking confirmation until setup is complete; a client-supplied completion flag
  cannot bypass it. Signing out, rejecting/cancelling bookings, and cancelling
  availability remain possible. Completing the profile does not grant approval.
- The profile screen adapts to its available width: an identity/photo card and
  checklist beside grouped details on desktop, and stacked mobile cards with
  touch-friendly fields and a full-width save button. The checklist updates as
  fields are entered, uploaded pictures have an instant preview, and invalid
  photo types/sizes are shown before submission. Progress reflects the current
  form; changes unlock the workspace only after a successful server-side save.
- Selecting a valid photo opens a local cropper. Drag or use arrow keys to
  reposition, adjust the zoom slider, reset, or cancel without replacing the
  previous picture. **Use photo** prepares a 512×512 JPEG; the circular overlay
  previews the avatar. The cropped file is uploaded only when the profile is
  saved. The country-code dropdown uses a compact fixed width so the national
  number receives the remaining space on desktop and mobile.
- The profile page includes section shortcuts for personal details, address,
  and offerings, a collapsible live completion checklist, an offerings character
  count, and a sticky save bar with an unsaved-changes indicator. Section cards
  distinguish private personal details from offerings visible to students.
  The checklist is hidden at 100% completion and reappears if a required detail
  becomes incomplete while editing.
- The workspace has five separate pages: **My calendar**, **Consultation requests**,
  **Availability**, **Consultation settings**, and **My profile**. The desktop sidebar
  keeps the four workspace links together; its bottom account dropdown shows the
  teacher photo/name/email with **My profile** and **Sign out**. The dropdown opens
  upward, supports keyboard activation, and closes on Escape or outside clicks.
  Desktop and mobile navigation highlight the current page, rather than scrolling
  through one long calendar page. Existing calendar URLs continue to work.
- Requests include status filters, totals, and paginated booking history (12 per
  page). Availability lists upcoming active windows (10 per page), the publishing
  form for approved teachers, cancellation controls, and links back to each date
  in the calendar. Unapproved teachers can review existing availability but cannot
  publish. Each action returns to its corresponding page; invalid forms retain
  their values and display errors there.
- Open **My profile** from the sidebar, mobile navigation, or header avatar to
  edit your name, email, contact number, birthday, sex, and address. Upload a
  JPG, PNG, or WebP profile picture up to 2 MB; replacing it removes the old file.
  Pictures appear on your profile and in your portal header/sidebar. Describe
  what help you offer directly in **What can you offer students?**; this saves
  immediately without admin confirmation. There is no subject picker in this
  profile form, and saving it preserves existing subject assignments. Students
  can see the description when browsing that teacher. Admin consultation permission
  for publishing schedules remains separate and cannot be self-granted.
  Run `php artisan migrate` for the new profile fields and `php artisan storage:link`
  on installations without the public storage link. Contact details are not
  added to student-facing calendars or public teacher listings.
- Phone input separates the country code from the national number, validates the
  selected country's format, and saves one canonical international number (for
  example PH +63 and 9123456789 becomes +639123456789). Country selection is saved
  so shared calling codes, such as US/Canada +1, restore correctly.
- Address fields match student registration: region, province, city/municipality,
  barangay, optional house/unit number, and optional postal code. Region, city,
  and barangay are required; province is optional for NCR. Suggestions use the
  PSGC location service with hierarchical endpoints, cached results, and stale
  response protection. Changing a parent clears dependent fields. All fields remain
  manually editable when the service is unavailable. Existing combined addresses
  are preserved and displayed until the teacher saves the new structured fields.
- After profile completion, the teacher portal opens with **My schedule calendar**. Browse previous/next
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
- Set the maximum time per consultation (**30 minutes or 1 hour**), automatic/manual booking approval, and
  student-name visibility in **Consultation settings**.
- Once approved, publish a future date, start/end time, and location. Availability
  must not overlap another active window, but need not fit fixed-size slots.
  Students choose a start and end time within the window at whole-minute precision.
  Each request must be at least one minute and no longer than the teacher's current
  maximum. Limit changes apply to new requests in all active windows; existing
  appointments retain their saved start and end times. The legacy `slot_minutes`
  columns are retained for historical data, not used as booking alignment rules.
- Manual bookings reserve the requested range as pending; automatic bookings are confirmed.
  Changing approval preference affects new bookings only.
- Review the time-ordered queue and confirm/reject requests, complete confirmed
  consultations, or cancel a confirmed booking.
- Cancelling an availability window automatically **rejects all pending and
  confirmed bookings** in it, removes it from booking calendars, and preserves
  history. Completed/cancelled/rejected records are unchanged.

## Student workflow

- Register with a grade and a section belonging to that grade.
- Browse approved teachers, their subjects, monthly calendar, available time ranges,
  and time-ordered queues. Enter a required consultation reason when booking.
- Select **Start time** and **End time**, with duration no longer than the teacher's
  30- or 60-minute maximum. For example, 1:00–2:00 PM is valid inside a 1:00–5:00 PM
  window when the teacher's maximum is one hour. Shorter and non-aligned ranges
  are allowed. End time must be after start time and stay within the window.
  Available ranges exclude the teacher's reservations and the signed-in student's
  reservations with other teachers. Client validation is advisory; the server
  locks teacher/student accounts and rechecks all time bounds, duration, permission,
  cancellations, and overlap inside the transaction. Pending/confirmed (and other
  non-cancelled/non-rejected) bookings reserve their full ranges. A student may
  consult multiple teachers, but cannot overlap them. Back-to-back bookings are allowed.
- **Request another time** allows a student to propose a date/start/end even when
  the approved teacher has no published availability. The same current duration
  maximum applies. These requests always start pending and require manual teacher
  approval, even when automatic approval is enabled for published sessions.
  Students cannot overlap their own pending proposals or other bookings across
  teachers. Different students may propose the same unreserved teacher time;
  proposals alone do not reserve teacher time. Published-session reservations do.
- Accepting a proposal or confirming a published booking immediately rejects
  overlapping pending proposals with a `time_taken` reason. Adjacent proposals
  remain unchanged. Unanswered proposals are rejected at their start time with
  an `expired` reason. Reasons appear in student history and teacher requests.
  Accepted proposals appear in the existing calendars without creating a public
  availability window or exposing private consultation reasons to other students.

### Automatic proposed-request rejection

Run Laravel's scheduler in deployment so expiry runs even without portal traffic:

```sh
php artisan schedule:work
```

Alternatively, configure cron/Windows Task Scheduler to run
`php artisan schedule:run` every minute from the project directory. The scheduled
`consultations:reject-stale-requests` command runs every minute with overlap
protection. Portal and monitoring requests also reconcile stale proposals before
rendering. Scheduled expiry is minute-granular; acceptance always rechecks the
exact start time and conflicts transactionally. To reconcile manually:

```sh
php artisan consultations:reject-stale-requests
```
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
so the calendar expands to fill the screen beside navigation. The teacher sidebar
links to the workspace pages with My profile in the bottom account dropdown;
the calendar link retains the selected month
and date while on the calendar page.
Bottom spacing includes the phone safe-area inset, so the bar does not cover page
content. Admin destinations are filtered by module permissions; Accounts retains
an accessible Roles link. Teacher tabs navigate to calendar, requests,
availability, preferences, and My profile, with exactly one active page.

An optional real-browser check is available at `tests/browser/mobile-responsive.cjs`.
With Playwright installed, demo accounts seeded, and the app served at
`http://127.0.0.1:8091`, run `node tests/browser/mobile-responsive.cjs`.
Set `APP_TEST_URL` for another local URL, `PLAYWRIGHT_MODULE` for an external
Playwright installation, or `CHROME_PATH` to use an installed Chrome executable.
Use an isolated development database: this browser check completes/updates the
demo teacher profile with fictional details and a generated portrait.

Local public-storage URLs default to `/storage`, keeping uploaded pictures on
the current host, port, and protocol even if `APP_URL` differs. Set
`PUBLIC_STORAGE_URL` only when intentionally serving these files from another
public base URL. Keep the public storage link in place (`php artisan storage:link`).
It checks teacher, student, and admin layouts at 320, 375, 390, 640, 768, 1024,
1280, 1440, 1920, and 2560px, including long teacher content, touch targets,
navigation, overflow, and full-width desktop sidebar sizing.

The mobile bottom navigation uses a centered, rounded floating dock with a
subtle active-icon highlight and consistent labels across roles. It retains
44px-or-larger touch targets, safe-area spacing, keyboard focus indicators,
and bottom content clearance. The profile save bar sits above the dock.
The mobile top bar shows only an avatar account trigger (an initial when no
photo is available). Tapping it reveals the account name and a POST sign-out
button. Outside click, moving focus outside, or Escape dismisses the dropdown;
Escape returns focus to the avatar. Desktop account controls are unchanged.
The mobile top bar is fixed to the top of the viewport while scrolling, with
safe-area and page-content clearance so it does not cover the page heading.

The student workspace shares the teacher portal's full-width desktop shell,
240px sidebar, card palette, section headings, and bottom account dropdown.
Desktop navigation contains Dashboard and Calendar; My profile and Sign out
are in the account menu, while all three student destinations remain in the
mobile bottom dock. Dashboard summary cards reflect scheduled days in the
current month, the next consultations shown (up to six), and total history.
Below 640px, the student dashboard summary matches the teacher dashboard:
three compact cards in one row, short Days/Upcoming/History labels, no leading
icons, and sky/green/amber values. Larger screens retain the full labels and icons.
The booking page places teacher details/calendar beside session cards on wide
screens and stacks them on mobile. Session forms retain required private
reasons, automatic/manual approval, and the existing queue privacy policy.

The student profile uses separate photo/school information and personal/address
cards, a sticky save bar, and an unsaved-changes indicator. School assignments
and name remain admin-managed. Students can crop an optional photo locally
before saving a 512×512 JPEG; cancellation keeps the previous selection.
Other student field requirements and server-side validation are unchanged.
Student addresses now have Region, Province, City/Municipality, Barangay,
House/Unit, and Postal code fields using the same hierarchical suggestions as
teacher profiles. Updating any address field requires region, city, and barangay;
province, house/unit, and postal code remain optional. A saved structured address
must remain complete. Manual entry is supported offline. The combined address is
composed when the structured fields are saved; legacy combined addresses are
shown read-only and preserved when unrelated details are edited.
Profile scripts are registered on workspace entry so photo controls also work
after Turbo navigation rather than only after a full page reload.

On mobile (below 1024px), tapping a teacher calendar date opens its daily
availability and consultations in a scrollable schedule modal without leaving
the month. Student published dates open all of that day's session cards, slots,
and privacy-filtered queues in the same modal; booking forms remain usable and
entered reasons survive closing/reopening it. Close with the close button,
Escape, or the backdrop. Focus returns to the date. Desktop retains normal
calendar navigation and inline agendas. Failed teacher schedule loads offer a
full-calendar link, and date links still work without JavaScript.

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
permission, teacher-only booking approval, chosen-time duration limits, privacy overrides,
queue order, cancellation/rebooking, history, invalid ranges, and conflicts.
Run `node --test tests/js/registration.test.cjs` for registration step validation.
