# Architecture

One Laravel 13 application with three areas that share models, policies and Blade components.

| Area | URL | Who | Layout |
|---|---|---|---|
| Public website | `/{locale}/...` (`/en`, `/ar`) | Everyone | `layouts/public.blade.php` |
| Patient portal | `/portal/...` | Role `patient` | `layouts/portal.blade.php` |
| Clinic dashboard | `/clinic/...` | Staff with confirmed 2FA | `layouts/clinic.blade.php` |

Stack: PHP 8.4, Laravel 13, Livewire 4 + Flux (official Livewire starter kit), Fortify (login, 2FA, passkeys, password reset), Tailwind 4, MySQL 8.4. Added packages: `spatie/laravel-permission` (roles) and `intervention/image` (website image processing, GD driver).

## Where things live

```
app/
  Enums/            Role, Permission, TreatmentStatus, ToothSurface, AppointmentStatus, ...
  Models/           Eloquent models. Concerns/: AppendOnly (blocks updates/deletes on history rows),
                    HasTranslations (*_translations rows per locale), Publishable (draft/published/scheduled)
  Policies/         PatientPolicy, TreatmentItemPolicy, ClinicalNotePolicy, AppointmentPolicy
  Http/Middleware/  SetLocale, EnsureStaffHasTwoFactor (staff.mfa), EnsureUserIsActive (active)
  Http/Controllers/ Public/ (read-only website), Portal/, Clinic/
  Http/Requests/    StoreContactMessageRequest
  Livewire/         ContentComponent (CMS base class), Forms/ (one form object per content type)
  Support/          Fdi, Locales, Audit, ClinicNavigation, ClinicDetails (editable contact details),
                    Seo (hreflang + JSON-LD), Slug (Unicode slugs), Markdown (safe rendering)
  Listeners/        Login/logout auditing (auto-discovered)
  Actions/          Business operations. Content/ (Save* per content type), Media/StoreMedia (image pipeline);
                    booking and treatment lifecycle arrive in Phase 4/5
config/clinic.php   Clinic settings: locales, patient number format, staff MFA, clinical disk
resources/css/theme.css   All brand and status colour tokens
resources/views/
  layouts/          public, portal, clinic, app (picks clinic/portal for settings pages), auth/*
  components/ui/    page-header, not-built, status-badge, empty-state, language-switcher, skip-link, picture, prose
  components/public/  section, service-card, dentist-card, post-card
  components/cms/   locale-tabs, image-upload, publication-badge, markdown-help
  pages/clinic/content/   CMS screens (Livewire single-file components)
  public/ portal/ clinic/   Area pages
routes/  web.php (public + /dashboard redirect), portal.php, clinic.php (incl. /clinic/content), settings.php
```

Rules of thumb:
- Controllers and Livewire components authorise, validate, then call an Action. Business rules live in Actions/Services, not in UI code.
- Authorisation is always server-side: route middleware + policies. Hidden menu items are only a convenience.
- Branding changes go in `theme.css` and `config/clinic.php`, never in backend code.

## Roles and permissions

Permissions say **what** a role may do (`App\Enums\Role::permissions()`); policies add **which records** (care team or ownership).

| | Admin | Dentist | Receptionist | Patient |
|---|---|---|---|---|
| Patient demographics | all | care team | all | own |
| Clinical records (history, notes, chart, documents) | all | care team | none | portal view only (Phase 5) |
| Treatment status changes | all | care team | none | none |
| Appointments | all | own calendar | all | own |
| Website content | yes | no | yes | no |
| Procedures, staff, settings, audit log | yes | no | no | no |

**Care team:** a dentist assigned in `patient_dentist`, or one with an appointment or treatment item for the patient (`Patient::hasCareTeamMember`, `Patient::scopeInCareTeamOf`).

Roles and permissions are synced from the enums by `App\Support\RolesAndPermissions::sync()` after every `php artisan migrate`, including a run with nothing to migrate (listener in `AppServiceProvider`). It is idempotent and creates no users, so production never depends on running a seeder. Changing `Role::permissions()` takes effect on the next deploy.

Production runs migrations only, never seeders, so code must not assume seeded rows exist:
- Query users by role with `User::havingRole(Role::X)`, which matches nothing if the role is missing. Spatie's `User::role('x')` throws `RoleDoesNotExist` and turns a missing role into a 500.
- Reference data the app needs (roles, FDI teeth) comes from migrations. The procedure catalogue is clinic data: production starts empty and an administrator fills it under **Procedure catalogue** ("Add starter procedures" adds only missing codes from `Actions\Procedures\AddStarterProcedures`; the local seeder uses the same action). Procedures used by treatment items can only be deactivated.
- Tests do not seed either: every test runs on a database built by migrations alone, and `ReferenceDataTest` covers the missing-role case.

## Data model

```
users ─┬─ staff_profiles (1:1)
       ├─ patients.user_id (1:1, portal account, optional)
       └─ model_has_roles → roles → role_has_permissions → permissions   (spatie)

patients ─┬─ patient_dentist (care team) ── users
          ├─ medical_histories / dental_histories   (versioned rows, encrypted text)
          ├─ appointments ── appointment_status_changes (append-only)
          ├─ treatment_plans ── treatment_items
          ├─ treatment_items ─┬─ teeth (FDI reference, 52 rows)
          │                   ├─ procedures (catalogue)
          │                   ├─ treatment_item_surfaces
          │                   └─ treatment_events (append-only lifecycle)
          ├─ clinical_notes (append-only, amends_id for corrections, encrypted)
          └─ documents (private disk, soft-deleted)

working_hours, schedule_breaks, blocked_periods (dentist_id NULL = clinic-wide)
appointment_requests (public/portal requests, approved into appointments)
audit_logs (append-only; who/what/which record, never clinical content)
```

Website content (Phase 2). Language-neutral fields sit on the parent row; text, slugs and SEO fields sit on a `*_translations` row per locale.

```
media (WebP full + thumbnail, alt text per locale) ◄── *_media_id on services, staff_profiles,
                                                        blog_posts, gallery_items
staff_profiles ── staff_profile_translations (display name, specialty, bio)
services ─┬─ service_translations (slug, summary, SEO) ── service_dentist
          ├─ service_sections (per locale, ordered)
          └─ service_faqs (per locale, ordered)
pages (key: about | privacy | terms) ── page_translations
blog_categories ── blog_category_translations
blog_posts (author, category, published_at) ── blog_post_translations
gallery_items (before/after media, consent who/when) ── gallery_item_translations
testimonials (one locale per row, consent who/when)
faqs (one locale per row)
settings (key → JSON; clinic contact details)
contact_messages (message encrypted, handled_at/by)
```

Clinical safety rules built into the schema and models:
- Treatment history is never overwritten. A status change inserts a `treatment_events` row and updates `treatment_items.status` in one transaction (Phase 4 action).
- `TreatmentEvent`, `ClinicalNote`, `AuditLog` and `AppointmentStatusChange` throw on update or delete.
- `TreatmentItem` cannot be deleted (only cancelled). Patients are archived (soft delete), never hard-deleted.
- No clinical foreign key cascades on delete.
- Free-text clinical fields use Laravel's `encrypted` cast (encrypted with `APP_KEY`). Back up the key with the database.
- Double booking: MySQL has no exclusion constraints, so the booking action locks the dentist row (`SELECT ... FOR UPDATE`), re-checks overlaps, then inserts (Phase 5).

Migrations: never edit a migration that has already run anywhere. Phase 2 edited the `staff_profiles` and `services` create migrations in place; `2026_10_12_100700_upgrade_phase_1_staff_and_service_columns` brings databases migrated in Phase 1 to the same shape (and copies existing profile text into a default-locale translation). It does nothing on a fresh install. New schema changes go in new migrations.

## Patient directory

`/clinic/patients` (Livewire pages in `resources/views/pages/clinic/patients`). Every screen authorises through `PatientPolicy` on load and again in each action.
- **List and search:** staff with `patients.view.all` see everyone; dentists only their care team (`Patient::scopeInCareTeamOf`). Search covers name in either order, patient number, email and phone. Phone matching compares digits without the leading 0 (MySQL `REGEXP_REPLACE`), so a local `079...` finds `+962 79...`. Search input is escaped, so `%` and `_` match literally.
- **Register and edit** (`patients.create` / `patients.update`, i.e. receptionists and admins) through `Actions\Patients\SavePatient`. A phone number or an email is required. Before creating a record the form lists possible duplicates (same phone, or same name and date of birth) and asks staff to confirm it is a different person. Care-team choices are limited to active dentists (plus anyone already on the team).
- **Archive and restore** (`patients.archive`, admins) through `Actions\Patients\ArchivePatient`: a soft delete, so nothing is lost and archived patients drop out of lists, search and URLs (404).
- **Audit:** `patient.created`, `patient.updated` (names of changed fields, never values), `patient.archived`, `patient.restored`, and `patient.viewed` each time someone opens a record.
- Form ids that pick the record to save come from the component's locked model, never from the browser-editable form (`form.patientId`, `form.userId`).

## Medical and dental history

`/clinic/patients/{patient}/history` (`viewClinical` to read, `writeClinical` to record: care-team dentists and admins; receptionists never).
- Each review inserts a new `medical_histories` / `dental_histories` row; both models are append-only, so earlier versions stay as they were. `Patient::latestMedicalHistory()` / `latestDentalHistory()` give the current version, and the page lists earlier ones.
- `no_known_allergies` records "patient was asked, has none", distinct from an empty (unknown) allergies field; the form refuses both at once.
- The patient overview shows recorded allergies in red, or a warning when allergies were never recorded, only to users who may see clinical data.
- Text fields are encrypted at rest. Audit entries (`clinical.history_viewed`, `clinical.medical_history_recorded`, `clinical.dental_history_recorded`) never contain the clinical text.

## Clinical notes

`/clinic/patients/{patient}/notes` (`viewClinical` to read; `ClinicalNotePolicy::create` to write).
- `ClinicalNote` is append-only. A correction (`Actions\Patients\RecordClinicalNote` with `amends`) is a new note whose `amends_id` points at the note it replaces; the timeline shows the latest version of each note, marked "Corrected", with earlier versions available on request.
- `ClinicalNotePolicy::amend`: only the author or an administrator, only the latest version (no forks), and the text must change.
- Note text is encrypted at rest; audit entries (`clinical.notes_viewed`, `clinical.note_created`, `clinical.note_amended`) never contain it.

## Treatment plans and items

`/clinic/patients/{patient}/treatment-plans` (`viewClinical` to read; `TreatmentItemPolicy::create` / `changeStatus` to act: care-team dentists and admins).
- `Actions\Treatments\AddTreatmentItem` inserts a `planned` item, its surfaces and a `created` event in one transaction. Whole-mouth procedures store no tooth; tooth and surface procedures need a valid FDI tooth, and surfaces must exist on that tooth (`Fdi::surfaces()`: incisal on incisors and canines, occlusal on premolars and molars).
- `Actions\Treatments\ChangeTreatmentStatus` locks the item row (`SELECT ... FOR UPDATE`), re-checks `TreatmentStatus::canTransitionTo()` against the stored status, sets the matching timestamp and writes a `status_changed` event, all in one transaction. Cancelling requires a reason (stored, encrypted, on the event). Completed and cancelled are final.
- A plan's status follows its items: active once any item moves, completed when every item is finished and at least one was completed (cancelled if all were cancelled).
- Audit: `clinical.treatments_viewed`, `treatment.plan_created`, `treatment.item_created`, `treatment.status_changed` (statuses and codes only).

## Dental chart

`/clinic/patients/{patient}/chart` (`viewClinical`). An inline SVG drawn in Blade (no JavaScript library):
- FDI layout as the dentist faces the patient: upper 18-11 | 21-28, lower 48-41 | 31-38 (primary: 55-51 | 61-65, 85-81 | 71-75). The SVG is `dir="ltr"` so it is never mirrored in Arabic.
- Each tooth has five regions; buccal faces outward (top on the upper arch, bottom on the lower), mesial faces the midline, and the centre is occlusal or incisal.
- Colour comes from the `--status-*` tokens in `theme.css`: per surface the most pressing status (in progress > planned > completed); treatments without surfaces tint and outline the whole tooth. Cancelled items are not drawn.
- Each tooth is a link to the treatments page filtered to that tooth, with an `aria-label` summary; a text list below repeats everything without colour.

## Patient documents

`/clinic/patients/{patient}/documents` (`viewClinical` to list and download, `writeClinical` to upload).
- `Actions\Patients\StoreDocument` stores the file on `config('clinic.storage.clinical_disk')` (default `local`, i.e. `storage/app/private`) under `patients/{id}/` with a random name. Accepted: PDF, JPG, PNG, WebP up to 10 MB, validated by content; the stored MIME type is detected server-side.
- `DocumentDownloadController` re-authorises, finds the document only within the patient in the URL, and responds with `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store` and a `sandbox` CSP. Only PDFs and images are served inline; everything else is an attachment.
- Removing is admin-only and a soft delete; the file stays on disk. Audit: `clinical.documents_viewed`, `clinical.document_uploaded`, `clinical.document_downloaded`, `clinical.document_removed`.
- Production note: PHP's `upload_max_filesize` must be at least 10M for the 10 MB limit to work (see README).

## Scheduling

Time: the clinic works in `config('clinic.timezone')` (production `Asia/Amman`); timestamps are stored in UTC. `Support\ClinicTime` converts clinic-local date + time to UTC and back. Weekly hours and breaks are clinic-local times of day; weekday 0 = Sunday.

- **Working hours and closures** (`/clinic/schedules`, `schedules.manage`: receptionists and admins): per dentist, several working ranges and breaks per weekday (`Actions\Scheduling\SaveWeeklySchedule` replaces the week in one transaction and refuses overlaps). Closures (`blocked_periods`) apply to one dentist or, with no dentist, the whole clinic.

## Staff management

Administrators (`staff.manage`) manage staff under `/clinic/staff`; screens extend `App\Livewire\StaffComponent`, which re-checks the permission on every request. `App\Actions\Staff\SaveStaffMember` creates and updates accounts:
- New staff accounts are marked verified and get a random password nobody knows. The administrator creates a **setup link** (`CreateSetupLink`, a normal password-reset link that expires per `config/auth.php`) and hands it over directly, so onboarding works without outgoing email. Staff then set up two-factor authentication at first sign-in.
- Deactivating an account deletes its database sessions and rotates its remember token, so the person is signed out at once. Records they created are kept.
- Administrators cannot demote or deactivate themselves, and the action refuses to remove the last active administrator.
- `ResetTwoFactor` clears someone else's authenticator (lost phone); they must enrol again.
- Dentists have a public profile (`staff_profiles` + translations): shown only when public, active and still a dentist. Changing a dentist's role hides the profile but keeps its text.
- Every change writes an audit entry (`staff.created`, `staff.updated`, `staff.setup_link_created`, `staff.two_factor_reset`).

## Website content (CMS)

Staff with the `content.manage` permission (admin, receptionist) edit the website under `/clinic/content`. Every CMS screen extends `App\Livewire\ContentComponent`, which re-checks the permission in `boot()`, so each save and delete is authorised again on the server, not just the first page load. Screens validate through a form object (`app/Livewire/Forms`) and save through an Action (`app/Actions/Content`); the demo seeder uses the same Actions.

| Content | Languages | Notes |
|---|---|---|
| Services | translations per locale, plus sections and FAQs per locale | Assigned dentists; cannot be deleted while appointments reference them |
| Blog posts, categories | translations per locale | `published_at` in the future = scheduled; deleting a category keeps its posts (uncategorised) |
| Pages (about, privacy, terms) | translations per locale | Fixed keys (`Page::KEYS`); routes are fixed, only content is editable |
| Gallery | translations per locale | Before/after images; cannot be published without recorded consent |
| Testimonials | one locale per row | Cannot be published without recorded consent |
| FAQs | one locale per row | General FAQ page; service FAQs live on the service |
| Media library | alt text per locale | Images in use cannot be deleted |
| Clinic details | per-locale name, address and hours | Stored in `settings`, cached (`ClinicDetails`). `ClinicDetails::name()` falls back to the default language, then to `config('clinic.name')` (`APP_NAME`) |
| Website messages | - | Contact form inbox; mark as handled |

Rules the code enforces:
- **Published** means `status = published` and `published_at` is not in the future (`Publishable::scopePublished`). Public controllers only query published rows, and a record with no translation in the current language is hidden in that language (404 on its URL).
- **Slugs** are made from the title in each language and kept as typed, so Arabic pages get Arabic URLs (`/ar/services/الحشوات`). They are unique per locale and do not change when the title is edited, so links keep working. Slugs and `hreflang` alternates are per translation.
- **Markdown** (service sections, posts, pages, FAQ answers) is rendered by `Support\Markdown` with raw HTML stripped and `javascript:`/`data:` links dropped.
- **Consent:** `GalleryItem` and `Testimonial` throw on save if they would be published without `consent_confirmed_at`, whatever code path saves them; the forms record who confirmed it and when.
- **Images** (`Actions\Media\StoreMedia`): JPG/PNG/WebP up to 8 MB, at least 200×200. EXIF orientation is applied and the image is re-encoded as WebP (1600px full and 480px thumbnail), which strips EXIF/GPS metadata. The original upload is not kept. Files live on the `public` disk (`php artisan storage:link`) and are rendered with `srcset` by `<x-ui.picture>`.
- **Contact form:** validation messages in both languages, a hidden honeypot field, and rate limits of 3 per minute and 20 per day per IP. The message body is encrypted at rest. It may contain health information, so only content staff can read it.

## SEO

- Each public page sets title, meta description, canonical URL, `hreflang` alternates (with `x-default`) and Open Graph tags through `layouts/public.blade.php`. Pages, posts and services have per-language SEO title and meta description fields.
- `/sitemap.xml` lists every published page in each language it exists in, with alternates.
- JSON-LD: `Dentist` (home, contact) built from the contact details, and `FAQPage` (FAQ page, service pages with FAQs).

## Localisation

- Public URLs carry the locale (`/en/services`, `/ar/services`) with `hreflang` alternates and a sitemap listing both.
- `/` redirects using the browser's `Accept-Language`.
- Portal and clinic use `users.locale`.
- `<html dir>` comes from `config/clinic.php` (`ar` = `rtl`). Templates use logical Tailwind utilities (`ms-`, `me-`, `start-`, `end-`) so layouts mirror automatically.
- Content is translated per record (see above). Services need a title in the default locale; blog posts need at least one language and can exist in Arabic only.
- Interface strings use `__()`. `lang/ar.json` and `lang/ar/validation.php` cover the public site and contact form. They are a first draft and need review by a native Arabic speaker before launch. Demo content (both languages) is placeholder text to be replaced.

## Phase plan

1. **Foundation (done):** auth, roles/policies, migrations, layouts, routes, seeds, tests.
2. **Public website and CMS (done):** public pages in English and Arabic, services/blog/pages/gallery/testimonials/FAQ CMS, media pipeline, contact form, SEO.
3. **In progress:** staff management, patient directory and search, medical and dental history (all done); clinic settings.
4. **Clinical records (done):** clinical notes, procedure catalogue, treatment plans and lifecycle, dental chart, documents.
5. **In progress:** working hours and closures (done); availability and appointments, booking requests, patient self-service.
6. Security hardening, audit log UI, backups, production readiness.
