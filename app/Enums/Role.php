<?php

namespace App\Enums;

/**
 * Application roles. Each role's permissions are defined in permissions(),
 * which RolesAndPermissionsSeeder syncs into the database.
 */
enum Role: string
{
    case Admin = 'admin';
    case Dentist = 'dentist';
    case Receptionist = 'receptionist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Dentist => __('Dentist'),
            self::Receptionist => __('Receptionist'),
            self::Patient => __('Patient'),
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Patient;
    }

    /**
     * @return list<Role>
     */
    public static function staff(): array
    {
        return [self::Admin, self::Dentist, self::Receptionist];
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),

            self::Dentist => [
                Permission::ClinicAccess,
                Permission::PatientsViewAssigned,
                Permission::ClinicalViewAssigned,
                Permission::ClinicalWrite,
                Permission::TreatmentsManage,
                Permission::AppointmentsViewOwn,
            ],

            self::Receptionist => [
                Permission::ClinicAccess,
                Permission::PatientsViewAll,
                Permission::PatientsCreate,
                Permission::PatientsUpdate,
                Permission::AppointmentsViewAll,
                Permission::AppointmentsManage,
                Permission::AppointmentRequestsManage,
                Permission::SchedulesManage,
                Permission::ContentManage,
            ],

            self::Patient => [
                Permission::PortalAccess,
            ],
        };
    }
}
