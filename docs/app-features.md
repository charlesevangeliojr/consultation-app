# App features and user guide

This guide explains what the consultation app does, who each feature is for, and
how the features work together. It describes the current app rather than a
step-by-step installation or button-by-button tutorial.

The app coordinates academic consultations between students and teachers.
Students request help, teachers manage their time and requests, and administrators
maintain school information, permissions, and account records.

## Contents

- [Roles at a glance](#roles-at-a-glance)
- [Public home page](#public-home-page)
- [Registration and sign-in](#registration-and-sign-in)
- [Student features](#student-features)
- [Teacher features](#teacher-features)
- [How consultation scheduling works](#how-consultation-scheduling-works)
- [Administrator features](#administrator-features)
- [Online presence and account status](#online-presence-and-account-status)
- [Privacy and visibility](#privacy-and-visibility)
- [Shared experience and automatic processes](#shared-experience-and-automatic-processes)
- [Common questions](#common-questions)

## Roles at a glance

| Role | Main purpose | Main features |
| --- | --- | --- |
| Visitor | Learn about the service and see public faculty hours. | Home page, published availability preview, registration, sign-in. |
| Student | Find academic support and follow consultation requests. | Dashboard, teacher browsing, calendar, bookings, proposed times, history, personal profile. |
| Teacher | Organize consultation hours and student requests. | Schedule calendar, request review, availability publishing, consultation preferences, profile and offerings. |
| Administrator | Maintain school data and manage authorized access. | School lists, accounts, student status, activity monitoring, teacher permission, roles, school settings, consultation monitoring. |
| Super Admin | Manage the system with full administrative access. | All administrative permissions and access to protected account management. |

Administrative features depend on the permissions assigned to the account. A user
with view-only permission can see the relevant information but cannot edit it.

### The overall consultation flow

1. The school maintains its grade and section lists and authorizes teachers.
2. A teacher completes their profile, describes their support, and publishes hours.
3. A student chooses a teacher and requests a published time or proposes another time.
4. The system checks the schedule; the request is automatically confirmed when
   eligible or waits for the teacher's decision.
5. The student follows the status in their dashboard, the teacher manages the
   consultation, and administrators can review the records in monitoring.
6. Completed or closed requests remain in history so everyone can understand the outcome.

## Public home page

The home page introduces the app and offers registration and sign-in. Its faculty
calendar previews upcoming published consultation hours from approved teachers,
including dates, times, and locations when provided.

The preview is a selection of published hours, not a complete list of every
appointment. A published window also does not guarantee that every minute is
still free; the signed-in booking screen checks the remaining time ranges.

The home page includes a TV-style faculty availability showcase. It displays
public hours, not student names or private appointment topics. It is a section of
the home page; there is no separate display-device management screen.

## Registration and sign-in

### Student accounts

Student registration collects:

- First and last name, with optional middle name and suffix.
- A unique **12-digit Learner Reference Number (LRN)**.
- Grade level and a section belonging to that grade.
- Philippine mobile number, birthday, and sex.
- Address details: region, province, city/municipality, barangay, and optional house/unit number.
- A password of at least eight characters, entered twice for confirmation.

The registration form is divided into account information, student details, and
password steps. Student registration does not require an email address or postal
code. Grade levels and sections come from the school's administrator-maintained lists.

Students sign in with **LRN + password** at `/portal/login`. The LRN is treated as
an identifier, so leading zeroes matter. A student can store an optional email in
their profile, but it does not replace the LRN for portal sign-in.

### Teacher accounts

Teacher registration collects the teacher's name, email, and password. Subjects
are not required. Teachers sign in with **email + password** at `/portal/login`.

Registration creates a teacher profile, but publishing consultation hours requires
both a complete profile and administrator-granted consultation permission.

### Administrator accounts

Administrators sign in with **email + password** at `/admin/login`. Administrative
access is assigned through account permissions and roles; there is no public
administrator-registration option. Authorized administrators can create accounts
and set a replacement password when managing an account.

Successful self-registration signs the student or teacher into the app. The portal
then opens the workspace appropriate to the account's role.

## Student features

### Dashboard

The dashboard brings together the student's own consultation information:

- **Scheduled days:** the number of distinct days with non-cancelled,
  non-rejected consultations in the current month.
- **Upcoming consultations:** the next six upcoming records shown, with teacher,
  date, time, reason, and status. The summary count refers to those displayed records.
- **Booking history & updates:** completed, cancelled, rejected, and past-starting
  consultations, with explanations for automatic rejection where applicable.
- **Personal month calendar:** marks days with scheduled consultations.

A time passing does not automatically mean that a consultation was completed.
The teacher marks a confirmed consultation completed; a past record may still
have its earlier status in history.

### Teacher browsing and calendar

Students can browse approved teachers, see their description of the help they
offer and any older subject labels still stored, and view published consultation windows on a monthly
calendar. Session details include the time range, location when supplied, maximum
consultation length, approval mode, and time-ordered queue.

Only teachers with consultation permission appear in the student booking list.
An approved teacher can still receive a **Request another time** proposal when
they have no published hours.

### Book a published consultation

A student selects a start and end time inside a teacher's published window and
provides a consultation reason. The app checks that the time is in the future,
within the available window, within the teacher's duration limit, and free of
student and teacher scheduling conflicts.

For published hours, the teacher's preference determines whether a new request
starts **Pending** or is immediately **Confirmed**.

### Request another time

This feature lets a student propose a future date and time directly to a teacher,
including a reason, without relying on a published availability window.

Proposals always require manual teacher acceptance, even when the teacher uses
automatic approval for published hours. An accepted proposal becomes a confirmed
consultation without creating a new public availability window.

### Cancel and book again

Students can cancel their own upcoming pending or confirmed bookings before the
start time. The cancelled record remains in history. To choose a different time,
the student cancels and submits a new request; there is no direct reschedule action.

### Student profile

Students can maintain an optional email, contact number, birthday, sex, LRN,
address, and optional profile picture. Name, grade level, and section are displayed
as school information managed by the administrator.

Address fields load saved region, province, city/municipality, barangay, and
house/unit details. Older registration addresses stored in the recognizable
combined registration format are separated into those fields when the profile
opens. Saving the profile stores the separate fields for future use.

When entering or updating a structured address, region, city, and barangay are
required. Province is optional, including for NCR. Postal code is not part of the
student registration or profile form. Location suggestions help fill the fields,
and manual entry remains available if suggestions cannot load.

Account status is managed in the admin area, not the student's profile.

## Teacher features

### Profile setup and offerings

Before using the main workspace, teachers complete their name, email, contact
number and calling-code selection, birthday, sex, address, profile picture, and
**What can you offer students?** description. A completion checklist explains
which required details are still missing.

The offerings description tells students what support the teacher provides.
Teachers can update it without admin confirmation. Subject selection and subject
management are no longer part of registration or the admin area. Older subject
assignments are preserved, but the offerings description explains the teacher's support.

Teacher addresses include region, province, city/municipality, barangay, optional
house/unit number, and optional postal code. The student postal-code removal does
not remove the teacher profile's optional postal-code field.

Completing a profile does not automatically grant consultation permission.
Teachers with incomplete profiles can still sign out and close existing records
through supported rejection, cancellation, or completion actions.

### My calendar

The teacher calendar combines published availability and scheduled consultations.
Teachers can move between months, return to Today, and select a day for a
time-ordered agenda with student names, consultation reasons, times, and locations.

Calendar markers identify published availability, pending requests, confirmed
bookings, and completed consultations. Cancelled availability and rejected or
cancelled bookings remain in records but are excluded from the scheduled calendar.

### Consultation requests

This page lists the teacher's requests and booking history in consultation-time
order. Filters separate pending, confirmed, completed, cancelled, and rejected
records, with totals for each status.

Teachers can:

- Confirm or reject pending requests.
- Mark confirmed consultations completed.
- Cancel confirmed bookings.
- Review consultation reasons and explanations for automatic rejection.

Only the assigned teacher can make these consultation decisions. An administrator
monitors the records but does not approve individual student bookings.

### Availability

An approved teacher can publish future consultation windows with a date, start
time, end time, and optional location. Multiple non-overlapping windows can be
published. A location is a text description, such as an office or room name.

The availability page lists upcoming active windows and provides cancellation
controls. Cancelling an entire window automatically rejects its pending and
confirmed bookings, while preserving the window and booking history. Completed,
previously cancelled, and previously rejected records are unchanged.

### Consultation settings

| Setting | What it controls |
| --- | --- |
| Maximum consultation time | New requests may last up to **30 minutes** or **1 hour**, depending on the selected limit. Shorter requests are allowed. |
| Automatic/manual approval | Whether new bookings inside published hours start confirmed or wait for teacher review. Proposed times always require manual approval. |
| Student-name visibility | Whether other students can see names in the teacher's queue, subject to the school-wide privacy setting. |

Changing the duration limit or approval mode affects new requests. Existing
bookings keep their saved times and statuses.

## How consultation scheduling works

### Published availability and proposed times

| Behavior | Published-window booking | Student-proposed time |
| --- | --- | --- |
| Requires published hours | Yes. | No. |
| Initial status | Pending or confirmed, based on teacher settings. | Always pending. |
| Reserves the student's requested time | Yes. | Yes, while the request is pending. |
| Reserves the teacher's time while pending | Yes. | No; another student may propose that same unreserved time. |
| Needs manual teacher acceptance | When manual approval is enabled. | Always. |
| Automatically rejected when an unanswered proposal reaches its start time | Not the published-booking expiry rule. | Yes. |

A pending proposal is a request, not a guaranteed meeting. When another accepted
consultation takes the same teacher time, overlapping pending proposals are
automatically rejected with a time-taken explanation.

### Time and conflict rules

- Requests must start in the future and end after they start.
- Published-window requests must fit entirely inside that window.
- Requests use whole-minute times and last at least one minute.
- Each request must stay within the teacher's current 30- or 60-minute maximum.
- A student cannot have overlapping non-cancelled, non-rejected consultations or
  requests, even with different teachers.
- Teacher reservations prevent another overlapping booking or acceptance.
- Back-to-back consultations are allowed: 1:00–1:30 and 1:30–2:00 do not overlap.
- The app rechecks conflicts when saving; a time can become unavailable if another
  request is accepted while someone is filling in a form.

**Example:** With published hours of 1:00–5:00 PM and a 60-minute maximum, a student
could request 1:10–1:40 PM or 2:00–3:00 PM if free. A request from 2:00–3:15 PM would
exceed the maximum. Requests do not have to align to fixed half-hour slots.

All schedules use **Philippine Standard Time (Asia/Manila, UTC+8)**, shown in the
scheduling screens. They are not converted to each visitor's device timezone.

### Booking status meanings

| Status | Meaning |
| --- | --- |
| Pending | A request is waiting for the teacher's decision. Whether it reserves teacher time depends on the request type above. |
| Confirmed | The teacher accepted the request, or a published booking was automatically approved. |
| Completed | The teacher marked a confirmed consultation completed. |
| Cancelled | The student or teacher cancelled a booking through an available cancellation action. |
| Rejected | The teacher declined the request, or the system rejected it because of cancelled availability, proposal expiry, or a conflicting accepted time. |

Pending records can be confirmed or closed; confirmed records can be completed or
cancelled. Completed, cancelled, and rejected records are historical rather than
open requests to approve again.

## Administrator features

### Admin dashboard

The admin dashboard identifies the signed-in account and its administrative access
level. The sidebar groups permitted pages into:

- **Overview:** Dashboard and Consultations (read-only consultation monitoring).
- **People & access:** Accounts and Roles & permissions.
- **Configuration:** Grades & sections and School settings.

On phones and tablets, there is no sidebar drawer or hamburger menu. Use the
bottom navigation for the main areas and the Accounts/roles tabs for
Roles & permissions. My profile and Sign out are in the avatar menu; the theme
button is in the top bar. The current page is highlighted, and navigation items
are hidden when the account lacks view permission.

### Grades & sections

Administrators maintain grade levels and sections under each grade.
These lists supply registration choices and school-profile assignments. A section
must belong to the student's selected grade. Items already used by school records
cannot be deleted until those references have been reassigned or removed.

### People & access: Accounts

Authorized administrators can create accounts, edit names and emails, assign roles
or account-specific permissions, set replacement passwords, and delete permitted
accounts. Deletion is a permanent removal action; marking a student inactive is
the separate status-management option.

The Accounts list shows name, LRN or email, role, student account status, online
status, and last-seen activity. Editing an account brings together account details,
access permissions, activity information, and its school profile.

Student school profiles include grade, section, LRN, contact details, birthday,
sex, and address. **Account activity & status** contains the student Active/Inactive
control, saved separately from class assignments and personal details.

Teacher school profiles let administrators grant or revoke **Consultation permission**
without selecting subjects. Permission is required to publish availability,
receive new student requests, and confirm bookings. Revoking it preserves existing
records and still permits supported closing/cancellation actions.

### People & access: Roles & permissions

Roles group permissions so accounts can share an access level. Account-specific
permissions can customize access for an individual user.

The current permission groups are:

- **Dashboard:** viewing the admin dashboard and consultation monitoring.
- **Settings:** viewing or editing school lists and school settings.
- **Accounts:** viewing, adding, editing, or deleting accounts and roles.

Administrators can grant only permissions they hold and manage only accounts
allowed by the access hierarchy. Super Admin accounts are protected from normal
deletion and hidden from permission-controlled administrators. Assigned roles
cannot be deleted while users still use them.

### Consultation monitoring

This read-only admin view brings together teacher availability and consultation
records, including participants, scheduled times, and statuses. It helps staff
understand consultation activity without taking over the teacher's approval role.

### School settings and admin profile

School settings control the displayed app/school name, logo, browser favicon, and
school-wide queue-name visibility. Logo and favicon images can be replaced or
removed. Branding changes appear across the relevant app pages.

The administrator's own profile supports updating their name, email, and password.

## Online presence and account status

These indicators answer different questions:

| Indicator | Meaning |
| --- | --- |
| Online | The account has authenticated activity within the last five minutes and has not signed out since that activity. |
| Offline | There is no recent activity within that window, or the account signed out. |
| Last seen | The latest recorded authenticated activity. **Not yet recorded** means no activity timestamp has been collected. |
| Active | The student's administrative account-status label is active. New student profiles default to active. |
| Inactive | An administrator marked the student inactive, or the two-year inactivity rule did. |

Visible signed-in portal/admin tabs send an activity heartbeat about once a minute.
Hidden or closed tabs stop contributing that heartbeat; online presence expires
after the five-minute window. The admin refreshes the Accounts page to see updated
presence and last-seen values.

### Automatic inactivity

Student accounts become inactive after **two years without recorded activity**.
The daily scheduled check applies the rule; opening the admin Accounts list also
checks for dormant student accounts.

For accounts that never sign in, the tracking baseline is used. Existing accounts
begin that period when activity tracking is installed because earlier activity
was not recorded. New accounts begin at creation. When an administrator reactivates
an inactive account, a new two-year inactivity period begins.

Returning online does not automatically overwrite an inactive status. An admin
can restore Active when appropriate. Active/Inactive is currently a monitoring
label: it does not itself block login or consultation booking. The automatic
inactivity rule applies to students, while online/last-seen monitoring covers
signed-in student, teacher, and admin accounts.

The app does not infer graduation, transfer, withdrawal, attendance, or enrollment
from a lack of activity. Administrators may use status when maintaining school
records, but inactivity alone does not establish why a student stopped using the app.

## Privacy and visibility

| Information | Visibility |
| --- | --- |
| Published teacher hours and supplied locations | Public home-page previews and signed-in booking screens. |
| Teacher offerings and any older subject labels | Student teacher-browsing screens. |
| A student's own booking reason and status | The student, assigned teacher, and authorized admin monitoring. |
| Other students' consultation reasons | Not displayed to students browsing a queue. |
| Student names in a queue | Controlled by the school privacy setting and, when allowed, the teacher preference. |
| Private contact details and addresses | Profile/account-management screens, not public schedules or student queue listings. |
| Account activity and status controls | Authorized admin account-management screens. |

The school's queue-name policy can **let teachers choose**, **hide all names**, or
**show all names** in student-facing queues. A school-wide choice overrides the
teacher preference. The assigned teacher and authorized administrators can still
identify the students they need to manage.

## Shared experience and automatic processes

### Photos and saving profiles

Students and teachers can select JPG, PNG, or WebP profile pictures up to 2 MB.
The photo cropper supports repositioning, zoom, reset, and cancel before preparing
the avatar. A new picture is uploaded when the profile is saved, not merely when
selected. Cancelling cropping preserves the previous choice.

Profile forms show validation messages and unsaved-change indicators. Details are
applied after a successful save. A rejected submission does not apply the invalid
changes; a selected picture may need to be chosen again.

### Desktop and mobile navigation

The app adapts to phones, tablets, and desktop screens. Wide layouts use sidebars
and multi-column workspaces; mobile layouts use bottom navigation and account
menus. Calendar dates on smaller screens can open a daily schedule panel without
leaving the month. History and long lists use pagination.

Address suggestions need the external location service. If it is unavailable,
users can still type address details, but saving profiles and making bookings
still require a connection to the app.

### Updates and background checks

Booking decisions and explanations appear in dashboards, calendars, request lists,
and history after navigation or refresh. There is no configured email, SMS, or
push-reminder delivery feature. Activity heartbeats update presence timestamps;
they do not live-refresh every booking list.

Two automatic processes run through the app's scheduler:

| Process | Effect |
| --- | --- |
| Proposed-request reconciliation | Rejects unanswered proposals at their start time or when another accepted consultation takes the time. Scheduled every minute, with additional checks during portal/monitoring activity. |
| Student inactivity check | Marks eligible student accounts inactive after two years. Scheduled daily, with additional checks on the admin Accounts list and authenticated activity. |

The person hosting the app needs to keep the scheduler running for background
checks to happen without someone opening a page. Setup details are in the
[project README](../README.md#local-setup-and-running-the-app).

## Common questions

**Does Online mean the teacher is free for a consultation?**

No. Online indicates recent app activity. Published hours and existing reservations
determine which consultation times can be requested.

**Does Pending mean my teacher accepted my request?**

No. Confirmed means accepted. A pending published booking reserves the teacher's
time while awaiting a decision; a pending proposal does not.

**Why did my proposed time disappear from upcoming consultations?**

Check history. It may have expired before approval, been rejected, been cancelled,
or conflicted with another accepted consultation. Past-starting records also move
into history even if their status has not been changed to completed.

**Why can I not find a teacher?**

Student browsing lists teachers with administrator-granted consultation permission.
A teacher without approval will not appear as a booking choice.

**Why can I not publish hours after completing my teacher profile?**

Profile completion and admin consultation permission are separate requirements.
Both are needed; profile completion alone does not grant publishing permission.

**Can a student change their class or account status?**

Those are admin-managed. Students can edit the personal fields available in My
profile; class assignments and account status are maintained in admin Accounts.

**Does Inactive mean I graduated or my account was deleted?**

No. It is a status label and keeps the account record. The app cannot establish
graduation from online activity. Deletion is a separate administrative action.

**Where are the technical setup and sample accounts?**

See the [documentation index](README.md),
[workflow reference](consultation-workflow.md), and
[demo accounts guide](demo-data.md).
