# Phone and desktop notifications (Web Push)

This is real Web Push: the browser's push service can wake `public/sw.js` and show
an OS notification even with this app's tabs closed. It is **not** page polling,
a fake toast, email/SMS, or a scheduled reminder system. Delivery is best-effort.
An offline/powered-off device, revoked permission, expired subscription, Focus/Do
Not Disturb, force-stopped browser, OS battery policy, or hosting/network failure
can delay or prevent delivery. Always check the app for the authoritative status.

## What sends an alert

- Teachers: a new pending request, an automatically confirmed booking, student
  cancellation, and approval/revocation of their scheduling permission.
- Students: confirmation, rejection (including automatically processed
  rejections), cancellation, and completion updates.
- Authorized, subscribed administrators: a new teacher profile needing
  scheduling approval, only when the admin can view and manage that account.

An event dispatches only after its database transaction commits. Failed or
rolled-back bookings do not send notifications. Push failures do not undo a
booking. Duplicate/no-op status saves do not notify again. Automatic expiry
alerts happen when expiry is processed by the existing scheduler or portal
reconciliation; this feature does not supply an external clock or scheduler.

## Server setup

1. Deploy the updated code, `public/sw.js`, `public/js/push-notifications.js`,
   `composer.json`, and `composer.lock`.
2. Install production dependencies (or build/upload `vendor` if there is no CLI):

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate
   ```

3. Generate one stable VAPID key pair **privately**:

   ```bash
   php artisan push:generate-keys
   ```

   To save directly to the local `.env` without printing either key, use
   `php artisan push:generate-keys --write`. It preserves an existing pair and
   refuses to overwrite an incomplete pair. It does not change the enabled flag
   or subject, and it does not deploy keys to your hosted server.

   Put the generated public/private keys in the hosted `.env`, never in a view,
   JavaScript file, SQL snapshot, screenshot, Git commit, or publicly downloadable
   archive. Keep the same pair across deployments. Rotating it requires devices to
   enable notifications again. Back up the private key securely.

4. Configure the hosted environment:

   ```dotenv
   APP_URL=https://your-school-domain.example
   WEBPUSH_ENABLED=true
   WEBPUSH_PUBLIC_KEY=<generated-public-key>
   WEBPUSH_PRIVATE_KEY=<generated-private-key>
   WEBPUSH_SUBJECT=https://your-school-domain.example
   WEBPUSH_QUEUED=false
   ```

   The subject can instead be a real `mailto:` contact. Never use an HTTP URL,
   invalid contact, or `localhost` as the production subject. Run
   `php artisan config:clear` and `php artisan view:clear` after changing config.
   No public setup/Artisan endpoint is provided.

5. Ensure PHP 8.2+, cURL, mbstring, and OpenSSL with P-256 elliptic curve support
   are available. GMP/bcmath improve performance. TLS verification stays enabled.
   The server must be allowed to make outbound HTTPS requests to browser push
   services (Google/FCM, Mozilla, Apple, and supported Windows push endpoints).
6. Log in on the device, open **navbar bell → Notification settings**, press
   **Enable notifications**, allow the browser prompt, then press **Send test
   notification**. A successful response means the vendor accepted the push;
   it does not guarantee the OS displayed it. Check device notification settings.

   Administrators also have Enable, Send test notification, and Disable controls
   in **School settings → Device notifications**. Teachers have them in
   **Consultation settings** too. The Dashboard no longer contains push controls.
   These use the same authenticated, rate-limited current-device endpoint—not a
   school-wide broadcast.

## Missing tables / InfinityFree without a terminal

`SQLSTATE[42S02] ... push_subscriptions doesn't exist` means the hosting database
has not received the notification migration. Uploading PHP files or setting VAPID
keys does not create database tables.

Use `php artisan migrate` where possible. If InfinityFree has no terminal:

1. Back up your hosted database.
2. In phpMyAdmin, select the application's existing database (the one in hosted
   `.env`), then use **Import** or the **SQL** tab.
3. Run [`database/sql/notifications-schema.sql`](../database/sql/notifications-schema.sql).
   It creates `push_subscriptions` and `account_notifications` only when absent.
   It inserts no accounts/keys/demo data and does not delete existing records.
4. Refresh the application. Later normal migrations recognize these tables and
   adopt them without recreating them.

Do **not** run `migrate:fresh`, reset the school database, or expose Artisan through
a public URL to fix this error. Keep `APP_DEBUG=false` in production. New code
shows a setup message instead of a raw SQL error when notification tables are absent.

## Notification inbox

Every role has a bell in the top navbar. Its badge counts unread updates for the
current account only. `/notifications` lists all that account's notifications,
with All/Unread filters, pagination, individual View update actions, and Mark all
as read. No user can read or mark another account's notifications.
Opening the inbox in a visible tab automatically marks its existing snapshot as
read and clears the bell number. Notifications arriving after that snapshot remain
unread; they get a new count and are cleared on the next inbox opening/refresh.
The history is retained, and loading the inbox in a background tab does not mark
it read until the tab becomes visible.

Inbox events are stored even when Web Push is disabled or a device has not opted
in. They begin after the inbox table is set up; previous events are not backfilled.
An event inside a rolled-back transaction is not retained. Admin teacher-approval
alerts go to all admins authorized to manage that teacher, not just subscribers.

Push alerts still go through the browser/OS and can appear while the app is
closed. While a page is open, push messages refresh its bell count; focusing the
page also refreshes the count. No background polling or external cron is added.
If new updates arrive while viewing the inbox, a Refresh inbox link appears.
Android heads-up banners, sound, and lock-screen behavior remain controlled by
Chrome/Android notification-channel settings and Do Not Disturb. The app cannot
force a banner over those settings.

**Windows/XAMPP:** if key creation fails with “Unable to create the key,” set
`OPENSSL_CONF` to your installed PHP's valid configuration **before** starting PHP:

```powershell
$env:OPENSSL_CONF = 'C:\xampp\php\extras\ssl\openssl.cnf'
php artisan push:generate-keys
```

Use that environment for local push delivery/testing as well. Do not disable TLS
verification to fix OpenSSL or certificate errors; configure the proper CA bundle.

## InfinityFree limitations

Delivery defaults to an event-driven HTTPS call during the request that commits a
change. This does not require cron, background polling, or a queue daemon. However,
free hosting execution limits, outbound restrictions, PHP capabilities, and push
provider errors can still make delivery fail. Verify on the actual hosted site
with **Send test notification** before claiming it works for your school.

InfinityFree does not offer cron on its free plan and prohibits reproducing cron
with repeated external requests. Do **not** add an external cron workaround or
public queue-runner URL. If consistent queued delivery, retries, scheduled
reminders, or high-volume notifications are required, move to compatible hosting.

On hosting with a real queue worker, set `WEBPUSH_QUEUED=true` and run Laravel's
queue worker normally. The feature does not start one automatically. Event-driven
delivery has no automatic retry loop; a temporary failure can mean a missed alert.

References: [InfinityFree cron policy](https://forum.infinityfree.com/t/cron-job-feature-is-now-disabled/80495),
[Web Push PHP requirements](https://github.com/web-push-libs/web-push-php).

## Device requirements

- **iPhone/iPad:** iOS/iPadOS 16.4+; install to the Home Screen, open that installed
  web app, sign in, and enable notifications from an explicit button press. An
  ordinary Safari tab is not sufficient for this flow. Enable separately for each
  installed app/device.
- **Android:** a supported browser with site notifications allowed; OS background
  and battery restrictions can affect delivery. Installation is recommended.
- **Desktop:** a browser supporting Push/Notifications; the tab can be closed,
  but a fully force-quit browser or disabled background services may prevent alerts.
- HTTPS is required (localhost is the development exception).

## Accounts and privacy

- Only authenticated users can manage subscriptions; all mutations use CSRF
  protection and separately named rate limits. Test delivery targets only the
  caller's selected device, never every user or another account.
- Subscription endpoints and keys are encrypted at rest using `APP_KEY`; do not
  rotate `APP_KEY` without planning for existing encrypted data. These fields are
  hidden from model serialization and are never written to application logs.
- Endpoints are restricted to known HTTPS browser push-service hosts, with no
  credentials/custom ports or redirects. Arbitrary URLs cannot be used to make
  the server fetch internal resources.
- Alerts contain only generic event text and public branding—not names, LRN,
  dates, times, or consultation reasons. Clicking opens a same-origin account
  page that still enforces login/role permissions. Notification content is not
  cached by the service worker.
- A browser profile uses one root service-worker subscription, shared by this
  origin's portal/Admin installations. It is bound to **one account at a time**;
  explicitly enabling another account can rebind that device. Separate browser
  profiles/devices are needed for simultaneous independent account subscriptions.
- Signing out detaches the current device on the server. A previously opted-in
  account can reattach an existing browser subscription after signing back in;
  other accounts are not silently opted in. Explicitly disabling also removes
  the browser subscription. Other devices remain subscribed.
- Expired subscriptions (HTTP 404/410 as reported by the push library) are removed.
  Permissions are never requested automatically. Device notification permission
  can also be revoked in the browser/OS settings at any time.

## Verification

```bash
php artisan test --filter="PushSubscriptionsTest|PushEventsTest|WebPushTransportTest"
node --test tests/js/pwa-worker.test.cjs
```

Automated crypto/delivery tests use generated test keys and a mocked HTTP client,
not real accounts or external pushes. Physical iPhone/Android/desktop delivery
must still be verified after configuring the hosted environment.
