# Nightly report: 12–13 October 2026

Production: http://20.216.17.199 (Web VM, `/var/www/dentist`). Every deploy below was preceded by a verified database backup (`~/backups/dentist-*.sql.gz`: gzip intact, all tables, "Dump completed"), and afterwards `/en` and `/ar` returned 200.

**Final status (13 Oct, ~04:35 UTC / 07:35 Amman):** the four items you asked for are done, and so are Phases 3, 4 and 5 of the plan: staff and patients, clinical records, scheduling, and the patient portal. All of it is live on `b5bb8f3`. Phase 6 (hardening) is next. Work paused between about 22:30 and 04:30 UTC (most likely the usage limit) and then continued.

## Needs you this morning

1. **Create the first administrator** on the server (this is not a test account, so I didn't create it):
   `cd /var/www/dentist && php artisan clinic:create-admin you@yourclinic.com --name="Your Name"`
   Then sign in and set up two-factor authentication.
2. **Website → Clinic details:** enter the clinic's real name in English and Arabic. The site still says "Dental Clinic".
3. **Procedure catalogue → "Add starter procedures"**, then review the names and durations.
4. **Dentists and staff:** add your dentists and receptionists, then send each person their setup link (the screen creates it).
   Then, per dentist: **Working hours and closures** (weekly hours and breaks; add public holidays as closures). Without hours, the calendar has no free times to offer.
5. **Approve one server change I was blocked from making** (auto mode refused it). Uploads over 2 MB currently fail: website images allow 8 MB and documents allow 10 MB.
   ```bash
   sudo tee /etc/php/8.4/apache2/conf.d/99-dentist.ini <<'INI'
   upload_max_filesize = 10M
   post_max_size = 12M
   memory_limit = 256M
   expose_php = Off
   INI
   sudo systemctl reload apache2
   ```
6. **Outgoing email is not configured** (`MAIL_MAILER=log`). Password resets and patient email verification only go to the log. Send me SMTP details (host, port, user, password, from address) and I'll set it up. Staff onboarding works without email thanks to the setup links.
7. **Arabic review:** the public site's Arabic text is a machine draft, and the clinic dashboard is English-only so far.

## Shipped (merge commits on `main`, all deployed)

| Time (UTC) | Merge | What |
|---|---|---|
| 20:43 | — | Passwordless `mysqldump` (`~/.my.cnf`, read from `.env`); backup command tested; `~/backups` private (700) |
| 20:49 | `a540e37` | **Production 500 fixed.** Roles and permissions are synced after every `migrate` (no seeder needed, no demo data), public pages can't crash on a missing role, a test reproduces the bug on an empty database, `clinic:create-admin`, registration in one transaction, log files group-writable |
| 20:50 | — | Server: storage permissions with default ACLs (deploys and Apache can both write), `APP_DEBUG=false` (visitors were seeing stack traces), `APP_URL` fixed, `git core.fileMode=false` |
| — | `8de5bd9` | Phase 2 docs: README deploy steps and one-time server setup (docs only, so no deploy) |
| 21:01 | `de8ac4b` | **Staff management:** accounts, roles, dentist website profiles, setup links, 2FA reset, deactivation signs the person out at once, audit |
| 21:12 | `1290158` | **Patient directory:** search (name, number, phone in local or international format, email), register with duplicate warning, overview, edit, archive/restore, audit including record views |
| 21:16 | `e28d5a3` | Font bundled with the build, so deploys no longer download it (cause of the 21:11 incident below) |
| 21:24 | `dec1995` | **Medical and dental history:** versioned (append-only); "no known allergies" kept distinct from "not recorded"; red allergy banner on the patient page for clinical staff only. Includes 1 migration |
| 21:28 | `3f5ec86` | Clinic name per language (CMS → Clinic details) |
| 21:32 | `7880ffe` | **Clinical notes:** append-only, corrections keep the original visible |
| 21:37 | `ff40804` | **Procedure catalogue** (admin), with "Add starter procedures" |
| 21:45 | `31d6086` | **Treatment plans and lifecycle:** planned → in progress → completed / cancelled (with a reason); row lock plus append-only events; surfaces validated per tooth |
| 21:52 | `e613c28` | **Dental chart:** SVG odontogram, FDI layout, permanent and primary teeth, colour plus a text alternative; checked visually by rendering it |
| 21:58 | `ffd68e8` | **Patient documents:** private disk, type checked by content, hardened downloads, admin-only removal |
| 22:06 | `d7f1c29` | **Working hours, breaks and closures** (clinic time zone, stored in UTC) |
| 22:15 | `40eecae` | **Appointment calendar:** free slots, no double booking (row lock), reschedule, status flow, cancellation reasons |
| 22:27 | `88e87ab` | **Online appointment requests** (public `/book`, English and Arabic) plus a reception inbox that books them. Also fixes the "Keep it" buttons (see below) |
| 04:33 | `b5bb8f3` | **Patient portal:** reception links accounts; next appointment, my appointments (cancel future ones), requests, my treatments. Staff get a patient Appointments tab |

Tests went from 184 to 298; Pint and PHPStan (level 7) are clean on every deployed merge (one exception below).

## Incidents

- **About 20:42 (before this session's work), ~3 min of 503.** A connection test over `ssh web` triggered the old forced-deploy command, and I cut its output short, which left maintenance mode on. I restored it by running the deploy to completion. You have since removed the forced command.
- **21:11, ~2 min of 503.** `npm run build` timed out downloading the font from fonts.bunny.net, and `deploy.sh` (`set -e`) stopped with the site in maintenance mode. I restored it by retrying the build and running the remaining steps by hand. Root cause fixed in `e28d5a3`, and every deploy since has been clean.

- **22:05, PHPStan error on main (not deployed).** Merge `94b8f6a` reached main with a PHPStan error, because my command chain hid PHPStan's exit code behind `tail`. It was fixed in the next commit (`d7f1c29`) before any deploy. Since then every merge runs a script that stops on any failed check.
- **Bug I shipped, now fixed:** from 21:45 until 22:27, the "Keep it" button on the treatment and appointment cancel forms raised an error (it changed a locked Livewire property). Nothing could be cancelled by accident; the form just wouldn't close. Fixed in `742dd30`, with regression tests.

## Decisions for you to review

- **Roles sync on every `migrate`**, through a listener. Seeders never run in production. Tests no longer seed roles, so the whole suite runs on a database built only by migrations, like production.
- **New staff accounts** are marked verified and have no usable password. The admin hands over a setup link, which expires after 60 minutes (Laravel's default).
- **Patients:** a phone number or an email is required. The duplicate check runs before registering (same phone, or same name and date of birth). Sex is female / male / not recorded.
- **Clinical history** rows are now append-only: each review adds a version.
- **Clinical notes:** only the author or an admin can correct a note, and only its latest version.
- **Treatments:** cancelling requires a reason. A plan's status follows its items. Plan notes are now encrypted (no production data existed).
- **Documents:** 10 MB limit; only an admin can remove a document, as a soft delete that keeps the file. There is no restore screen yet.
- **Clinic dashboard language:** English only, consistent with the existing translations. Public site, booking form and portal are in English and Arabic (the Arabic is a draft).
- **Scheduling:** 15-minute slot grid; weekday 0 = Sunday; "copy to empty days" skips Friday. Booking outside working hours needs an explicit tick. Dentists see only their own calendar and can't book (reception and admins do).
- **Online requests are not bookings:** the public form never shows free times. Reception calls the person back and books it from the inbox (matching an existing patient by phone, or registering a new one).
- **Portal:** patients can cancel future appointments online (with a reason) but not change them. "My treatments" hides notes and cancelled items. **"My documents" is intentionally still closed**: tell me which document types patients may download (for example X-rays and consent forms) and I'll open it.
- **Form tampering:** the editable form ids in the Phase 2 CMS forms (testimonials, FAQs, categories) were left as they are. Anyone able to change them can already edit all of those items, so there's nothing extra to reach.

## Recommendations (not applied: your files or infrastructure)

- `~/deploy.sh`: build assets **before** `php artisan down`, and on a failure before migrations, run `php artisan up` (a trap), so a failed build never takes the site down.
- The backup command in CLAUDE.md has no `pipefail`. A failed `mysqldump` still leaves a small, valid-looking `.gz`. I verified every backup tonight; consider `set -o pipefail` plus a check for "Dump completed".
- Turn on Azure VM backup if you haven't already.
- HTTPS, then `SESSION_SECURE_COOKIE=true`, plus security headers (planned for Phase 6).

## In progress / next

Nothing is half-done: every branch is merged and deployed. Suggested next steps (Phase 6 and loose ends):
1. HTTPS (domain plus certificate), then secure cookies and security headers (CSP, HSTS, X-Frame-Options).
2. An audit log screen for admins (every action is already being recorded).
3. Portal documents, once you decide what to share.
4. Email (once SMTP is configured): appointment confirmations and reminders.
5. An Arabic translation of the clinic dashboard, and a native speaker's review of the existing Arabic.
6. A restore screen for removed documents and archived patients (archived patients can already be restored from the patient list).
