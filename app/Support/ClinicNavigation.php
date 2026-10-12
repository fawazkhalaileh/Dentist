<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;

/**
 * Single definition of the clinic dashboard navigation. Items are filtered by
 * the same abilities the routes enforce, so staff only see what they can open.
 * Hiding an item is a convenience; the route middleware is the actual control.
 */
final class ClinicNavigation
{
    /**
     * @return list<array{heading: string, items: list<array{label: string, route: string, icon: string, active: string}>}>
     */
    public static function for(User $user): array
    {
        $content = $user->can(Permission::ContentManage->value);

        $groups = [
            [
                'heading' => 'Overview',
                'items' => [
                    self::item('Dashboard', 'clinic.dashboard', 'home', true),
                ],
            ],
            [
                'heading' => 'Patients',
                'items' => [
                    self::item('Patients', 'clinic.patients.index', 'users', $user->can('viewAny', Patient::class), 'clinic.patients.*'),
                    self::item('Appointments', 'clinic.appointments.index', 'calendar-days', $user->can('viewAny', Appointment::class)),
                    self::item('Appointment requests', 'clinic.appointment-requests.index', 'inbox', $user->can(Permission::AppointmentRequestsManage->value)),
                ],
            ],
            [
                'heading' => 'Clinical',
                'items' => [
                    self::item('Procedure catalogue', 'clinic.procedures.index', 'clipboard-document-list', $user->can(Permission::ProceduresManage->value)),
                ],
            ],
            [
                'heading' => 'Website',
                'items' => [
                    self::item('Services', 'clinic.content.services.index', 'squares-2x2', $content, 'clinic.content.services.*'),
                    self::item('Blog', 'clinic.content.blog.index', 'newspaper', $content, 'clinic.content.blog.*'),
                    self::item('Pages', 'clinic.content.pages.index', 'document-text', $content, 'clinic.content.pages.*'),
                    self::item('Gallery', 'clinic.content.gallery.index', 'photo', $content, 'clinic.content.gallery.*'),
                    self::item('Testimonials', 'clinic.content.testimonials.index', 'chat-bubble-left-right', $content),
                    self::item('FAQs', 'clinic.content.faqs.index', 'question-mark-circle', $content),
                    self::item('Media library', 'clinic.content.media.index', 'rectangle-stack', $content),
                    self::item('Clinic details', 'clinic.content.clinic-details', 'map-pin', $content),
                    self::item('Website messages', 'clinic.content.messages.index', 'envelope', $content),
                ],
            ],
            [
                'heading' => 'Administration',
                'items' => [
                    self::item('Dentists and staff', 'clinic.staff.index', 'identification', $user->can(Permission::StaffManage->value)),
                    self::item('Working hours', 'clinic.schedules.index', 'clock', $user->can(Permission::SchedulesManage->value)),
                    self::item('Clinic settings', 'clinic.settings.index', 'cog-6-tooth', $user->can(Permission::SettingsManage->value)),
                    self::item('Audit log', 'clinic.audit.index', 'shield-check', $user->can(Permission::AuditView->value)),
                ],
            ],
        ];

        $visible = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter($group['items']));

            if ($items !== []) {
                $visible[] = ['heading' => $group['heading'], 'items' => $items];
            }
        }

        return $visible;
    }

    /**
     * @return array{label: string, route: string, icon: string, active: string}|null
     */
    private static function item(string $label, string $route, string $icon, bool $allowed, ?string $active = null): ?array
    {
        return $allowed
            ? ['label' => $label, 'route' => $route, 'icon' => $icon, 'active' => $active ?? $route]
            : null;
    }
}
