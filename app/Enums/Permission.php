<?php

namespace App\Enums;

/**
 * Permissions describe WHAT a role may do. Policies combine them with
 * record ownership / care-team checks to decide WHICH records.
 */
enum Permission: string
{
    // Areas
    case ClinicAccess = 'clinic.access';
    case PortalAccess = 'portal.access';

    // Patient demographics
    case PatientsViewAll = 'patients.view.all';
    case PatientsViewAssigned = 'patients.view.assigned';
    case PatientsCreate = 'patients.create';
    case PatientsUpdate = 'patients.update';
    case PatientsArchive = 'patients.archive';

    // Clinical records: medical/dental history, notes, chart, documents
    case ClinicalViewAll = 'clinical.view.all';
    case ClinicalViewAssigned = 'clinical.view.assigned';
    case ClinicalWrite = 'clinical.write';
    case TreatmentsManage = 'treatments.manage';

    // Scheduling
    case AppointmentsViewAll = 'appointments.view.all';
    case AppointmentsViewOwn = 'appointments.view.own';
    case AppointmentsManage = 'appointments.manage';
    case AppointmentRequestsManage = 'appointment-requests.manage';
    case SchedulesManage = 'schedules.manage';

    // Administration
    case ProceduresManage = 'procedures.manage';
    case ContentManage = 'content.manage';
    case StaffManage = 'staff.manage';
    case SettingsManage = 'settings.manage';
    case AuditView = 'audit.view';
}
