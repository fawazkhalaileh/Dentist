# Architecture

One Laravel 13 application with three areas that share models, policies and Blade components.

| Area | URL | Who | Layout |
|---|---|---|---|
| Public website | `/{locale}/...` (`/en`, `/ar`) | Everyone | `layouts/public.blade.php` |
| Patient portal | `/portal/...` | Role `patient` | `layouts/portal.blade.php` |
| Clinic dashboard | `/clinic/...` | Staff with confirmed 2FA | `layouts/clinic.blade.php` |

Stack: PHP 8.4, Laravel 13, Livewire 4 + Flux (official Livewire starter kit), Fortify (login, 2FA, passkeys, password reset), Tailwind 4, MySQL 8.4. One added package: `spatie/laravel-permission`.

## Where things live

```
app/
  Enums/            Role, Permission, TreatmentStatus, ToothSurface, AppointmentStatus, ...
  Models/           Eloquent models; Concerns/AppendOnly blocks updates/deletes on history rows
  Policies/         PatientPolicy, TreatmentItemPolicy, ClinicalNotePolicy, AppointmentPolicy
  Http/Middleware/  SetLocale, EnsureStaffHasTwoFactor (staff.mfa), EnsureUserIsActive (active)
  Http/Controllers/ Public/, Portal/, Clinic/
  Support/          Fdi (tooth numbering), Locales, Audit, ClinicNavigation
  Listeners/        Login/logout auditing (auto-discovered)
  Actions/          Business operations (booking, treatment lifecycle) - Phase 4/5
config/clinic.php   Clinic settings: locales, patient number format, staff MFA, clinical disk
resources/css/theme.css   All brand and status colour tokens
resources/views/
  layouts/          public, portal, clinic, app (picks clinic/portal for settings pages), auth/*
  components/ui/    page-header, not-built, status-badge, empty-state, language-switcher, skip-link
  public/ portal/ clinic/   Area pages
routes/  web.php (public + /dashboard redirect), portal.php, clinic.php, settings.php
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

Roles are synced from the enums by `RolesAndPermissionsSeeder` (idempotent; run on every deploy).

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

services ── service_translations (per-locale slug/SEO) ── service_dentist
working_hours, schedule_breaks, blocked_periods (dentist_id NULL = clinic-wide)
appointment_requests (public/portal requests, approved into appointments)
audit_logs (append-only; who/what/which record, never clinical content)
```

Clinical safety rules built into the schema and models:
- Treatment history is never overwritten. A status change inserts a `treatment_events` row and updates `treatment_items.status` in one transaction (Phase 4 action).
- `TreatmentEvent`, `ClinicalNote`, `AuditLog` and `AppointmentStatusChange` throw on update or delete.
- `TreatmentItem` cannot be deleted (only cancelled). Patients are archived (soft delete), never hard-deleted.
- No clinical foreign key cascades on delete.
- Free-text clinical fields use Laravel's `encrypted` cast (encrypted with `APP_KEY`). Back up the key with the database.
- Double booking: MySQL has no exclusion constraints, so the booking action locks the dentist row (`SELECT ... FOR UPDATE`), re-checks overlaps, then inserts (Phase 5).

Content still to come in Phase 2: service sections/FAQs, blog posts and categories, gallery, testimonials, FAQs, pages, media.

## Localisation

- Public URLs carry the locale (`/en/services`, `/ar/services`) with `hreflang` alternates and a sitemap listing both.
- `/` redirects using the browser's `Accept-Language`.
- Portal and clinic use `users.locale`.
- `<html dir>` comes from `config/clinic.php` (`ar` = `rtl`). Templates use logical Tailwind utilities (`ms-`, `me-`, `start-`, `end-`) so layouts mirror automatically.
- Interface strings use `__()`. Arabic translations (`lang/ar.json`) are not written yet.

## Phase plan

1. **Foundation (done):** auth, roles/policies, migrations, layouts, routes, seeds, tests.
2. Public pages, services CMS, blog CMS, SEO.
3. Patient directory and search, staff management, clinic settings.
4. Interactive SVG odontogram and treatment workflow actions.
5. Scheduling, availability, booking requests, patient self-service.
6. Security hardening, audit log UI, backups, production readiness.
