<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## School consultation app

For an overview of what the app does, read the **[App features and user guide](docs/app-features.md)**.
It explains student, teacher, and admin features, consultation rules, privacy,
profiles, and account activity in plain language. The [documentation index](docs/README.md)
links to all guides.

See [the consultation workflow guide](docs/consultation-workflow.md) for database
setup, admin school lists and approvals, teacher preferences, student booking,
queue privacy, cancellation rules, and verification commands.

For uploaded images missing on InfinityFree or other shared hosts, see
[Shared-hosting image setup](docs/shared-hosting-images.md). It explains the
symlink-free `/media` image route, hosted configuration, and transferring uploads.

For sample accounts and a MySQL/MariaDB data snapshot, see
[Demo credentials and SQL import](docs/demo-data.md).

### Local setup and running the app

Run these commands from the project directory after installing dependencies and
configuring your database connection in `.env`.

Apply database migrations and run the default seeder:

```sh
php artisan migrate --seed
```

To also create fictional demo accounts and consultation records (optional,
development/testing only):

```sh
php artisan db:seed --class=DemoConsultationSeeder
```

See [Demo credentials and SQL import](docs/demo-data.md) for the demo login details.
These commands do not reset the database. Avoid `migrate:fresh` on a database
containing records you want to keep.

Start the local development server:

```sh
php artisan serve
```

Open **http://127.0.0.1:8000**. Student and teacher login is at `/portal/login`;
administrator login is at `/admin/login`.

In a separate terminal, keep the scheduler running for automatic request expiry
and the daily student-account inactivity check:

```sh
php artisan schedule:work
```

### Account activity monitoring

Under **Admin → User Management → Accounts**, administrators can view online/offline
presence and last-seen timestamps. Open a student's **Edit** page to manage
**Active / Inactive** under **Account activity & status**.

- Online means authenticated activity within the last five minutes; visible portal
  tabs send an activity heartbeat every minute. Signing out marks the account offline.
- Student accounts are marked inactive after two years without activity. Existing
  accounts start their tracking period when the activity migration is applied;
  previous activity is unknown. New accounts start at creation.
- Admin reactivation resets the two-year inactivity period. Returning online does
  not override an inactive status. Status is an administrative monitoring label,
  separate from online presence, and does not itself block login or certify enrollment.
- The scheduler checks daily. The admin account list also reconciles inactive
  students when opened. To run the check manually:

```sh
php artisan accounts:deactivate-inactive
```

### Admin dashboard charts

The admin dashboard includes six-month consultation activity and booking-status
charts, monthly totals, and a reporting-month filter. Counts use scheduled
consultation dates (in the application timezone), not the date a request was
submitted. Expand **View monthly data** for the chart's underlying values.

### Consultation calendar

The app uses **Philippine Standard Time (`Asia/Manila`, UTC+8)** for schedules,
date filters, reporting, activity, and automatic request expiry. Student/teacher
time inputs are Philippine times, not the device's local timezone. After deploying
the timezone change, run `php artisan config:clear` and restart any long-running
scheduler/queue processes. Existing saved date/time values are not automatically
shifted; see the workflow guide before converting historical UTC records.

The admin **Consultations** page uses a month calendar. Select a date to see that
day's availability and booking records, then select a booking for its full
read-only details. Month navigation and **Today** support past and future dates;
calendar markers count all bookings, including proposed requests and history.

Teacher scheduling approval is managed from **Accounts → select a teacher →
Scheduling permission**, or **Consultations → Teachers & permission**. Authorized
admins can approve or revoke scheduling. Unapproved teachers cannot publish
availability, receive new requests, or confirm bookings. Approval does not bypass
required teacher profile setup or let administrators approve individual bookings.

### Light and dark appearance

All portals and public pages support light and dark mode. Use the sun/moon button
in the **top navigation bar** to choose **Light mode** or **Dark mode**, on desktop
and mobile. Login and registration screens have the same control at the top right.

On the first visit, appearance follows your device preference. A manual choice
is saved in this browser and shared across pages and tabs; it does not change
other users' devices. Theme switching also works when browser storage is blocked,
but the choice cannot persist after reloading in that case.

### Teacher and student navigation tours

Teachers and students get a short role-specific walkthrough on their first visit.
Use **Tour** in the top navbar to replay it, with Next, Back, Skip, and Open page
actions. On mobile the replay button uses a question-mark icon. Finished/skipped
tours are remembered on the account, including across browsers. No consultation
data is stored in the browser. On the first authenticated visit, the tour takes
priority over the install suggestion. Incomplete teachers see it on profile setup.

### Install on a phone or tablet (PWA)

**Install app** uses the browser's native confirmation on supported Android
browsers when an install prompt is available. iPhone/iPad use the numbered Safari
Share → Add to Home Screen steps; iOS does not expose a direct install-prompt API.
The landing-page footer also has **Install app**, so users can retry after
dismissing the suggestion. A failed native prompt falls back to installation steps.
The manifest link includes `crossorigin="use-credentials"` for same-origin
hosting security cookies (including InfinityFree's browser-check cookie).
Without it, a host may return challenge HTML instead of the manifest JSON.

On a supported phone or tablet, the app shows a centered **Install app** popup
with a dark backdrop. Dismiss it with the close button, Escape, or a backdrop tap.
When the browser supports its own install prompt, the button opens it; otherwise,
**How to install** explains the browser menu or Safari's **Share → Add to Home
Screen** flow. This installs a progressive web app, **not an APK download**.
Dismissed suggestions stay hidden for seven days in that browser.

- Deploy over **HTTPS** with the web server pointing to Laravel's `public`
  directory. `localhost` and `127.0.0.1` also work for development, but plain HTTP
  on a LAN IP (for example `http://192.168.x.x`) is not installable.
- Allow `/manifest.webmanifest`, `/sw.js`, and `/pwa/*` to be served publicly.
  Keep `/sw.js` revalidatable (do not configure immutable long-term caching).
- The school name comes from admin settings. The installed app opens
  `/consultations`, which routes signed-in users to the appropriate workspace;
  guests see the portal login. Admin login remains available at `/admin/login`.
- Installed/standalone launches show a theme-aware loading screen that clears
  when the page is ready, with an eight-second safety timeout.
- An internet connection is required for accounts and bookings. The service
  worker caches **only** `/pwa/offline.html`, never private pages, submissions,
  photos, or consultation details. Offline visits show a reconnect screen.
- Native installation and its system splash depend on the browser and OS.
  In-app browsers may require opening the site in Chrome, Edge, or Safari first.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
