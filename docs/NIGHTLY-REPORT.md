# Nightly report: 12–13 October 2026

Production: http://20.216.17.199 (Web VM, `/var/www/dentist`). Every deploy below was preceded by a verified database backup (`~/backups/dentist-*.sql.gz`: gzip intact, all tables, "Dump completed"), and afterwards `/en` and `/ar` returned 200.

**Status when this was written:** the four items you asked for are done. Phase 3 (staff, patients, clinic details) and Phase 4 (clinical records) are both live. Phase 5 (scheduling) is next. This file is updated as work continues.

## Needs you this morning

1. **Create the first administrator** on the server (this is not a test account, so I didn't create it):
   `cd /var/www/dentist && php artisan clinic:create-admin you@yourclinic.com --name="Your Name"`
   Then sign in and set up two-factor authentication.
2. **Website → Clinic details:** enter the clinic's real name in English and Arabic. The site still says "Dental Clinic".
3. **Procedure catalogue → "Add starter procedures"**, then review the names and durations.
4. **Dentists and staff:** add your dentists and receptionists, then send each person their setup link (the screen creates it).
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

Tests went from 184 to 262; Pint and PHPStan (level 7) are clean on every merge.

## Incidents

- **About 20:42 (before this session's work), ~3 min of 503.** A connection test over `ssh web` triggered the old forced-deploy command, and I cut its output short, which left maintenance mode on. I restored it by running the deploy to completion. You have since removed the forced command.
- **21:11, ~2 min of 503.** `npm run build` timed out downloading the font from fonts.bunny.net, and `deploy.sh` (`set -e`) stopped with the site in maintenance mode. I restored it by retrying the build and running the remaining steps by hand. Root cause fixed in `e28d5a3`, and every deploy since has been clean.

## Decisions for you to review

- **Roles sync on every `migrate`**, through a listener. Seeders never run in production. Tests no longer seed roles, so the whole suite runs on a database built only by migrations, like production.
- **New staff accounts** are marked verified and have no usable password. The admin hands over a setup link, which expires after 60 minutes (Laravel's default).
- **Patients:** a phone number or an email is required. The duplicate check runs before registering (same phone, or same name and date of birth). Sex is female / male / not recorded.
- **Clinical history** rows are now append-only: each review adds a version.
- **Clinical notes:** only the author or an admin can correct a note, and only its latest version.
- **Treatments:** cancelling requires a reason. A plan's status follows its items. Plan notes are now encrypted (no production data existed).
- **Documents:** 10 MB limit; only an admin can remove a document, as a soft delete that keeps the file. There is no restore screen yet.
- **Clinic dashboard language:** English only, consistent with the existing translations.
- **Form tampering:** the editable form ids in the Phase 2 CMS forms (testimonials, FAQs, categories) were left as they are. Anyone able to change them can already edit all of those items, so there's nothing extra to reach.

## Recommendations (not applied: your files or infrastructure)

- `~/deploy.sh`: build assets **before** `php artisan down`, and on a failure before migrations, run `php artisan up` (a trap), so a failed build never takes the site down.
- The backup command in CLAUDE.md has no `pipefail`. A failed `mysqldump` still leaves a small, valid-looking `.gz`. I verified every backup tonight; consider `set -o pipefail` plus a check for "Dump completed".
- Turn on Azure VM backup if you haven't already.
- HTTPS, then `SESSION_SECURE_COOKIE=true`, plus security headers (planned for Phase 6).

## In progress / next

- Phase 5: scheduling (working hours, appointments, booking requests).
