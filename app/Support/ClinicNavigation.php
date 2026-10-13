<?php

namespace App\Support;

use App\Enums\AppointmentRequestStatus;
use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\ContactMessage;
use App\Models\Patient;
use App\Models\User;

/**
 * Single definition of the clinic dashboard navigation. Items are filtered by
 * the same abilities the routes enforce, so staff only see what they can open.
 * Hiding an item is a convenience; the route middleware is the actual control.
 * Pages that are not built yet are left out so first-time users never land on them.
 */
final class ClinicNavigation
{
    /**
     * @return list<array{heading: ?string, icon: ?string, collapsible: bool, active: string, items: list<array{label: string, route: string, icon: string, active: string, badge: ?int}>}>
     */
    public static function for(User $user): array
    {
        $content = $user->can(Permission::ContentManage->value);
        $requests = $user->can(Permission::AppointmentRequestsManage->value);

        $groups = [
            [
                'heading' => null, 'icon' => null, 'collapsible' => false, 'active' => '',
                'items' => [
                    self::item('Home', 'clinic.dashboard', 'home', true),
                    self::item('Calendar', 'clinic.appointments.index', 'calendar-days', $user->can('viewAny', Appointment::class)),
                    self::item('Patients', 'clinic.patients.index', 'users', $user->can('viewAny', Patient::class), 'clinic.patients.*'),
                    self::item('Requests', 'clinic.appointment-requests.index', 'inbox-arrow-down', $requests, null,
                        $requests ? AppointmentRequest::where('status', AppointmentRequestStatus::Pending->value)->count() : null),
                ],
            ],
            [
                'heading' => 'Clinic', 'icon' => null, 'collapsible' => false, 'active' => '',
                'items' => [
                    self::item('Procedures', 'clinic.procedures.index', 'clipboard-document-list', $user->can(Permission::ProceduresManage->value)),
                    self::item('Working hours', 'clinic.schedules.index', 'clock', $user->can(Permission::SchedulesManage->value)),
                    self::item('Dentists and staff', 'clinic.staff.index', 'identification', $user->can(Permission::StaffManage->value), 'clinic.staff.*'),
                ],
            ],
            [
                'heading' => 'Website', 'icon' => 'globe-alt', 'collapsible' => true, 'active' => 'clinic.content.*',
                'items' => [
                    self::item('Clinic details', 'clinic.content.clinic-details', 'map-pin', $content),
                    self::item('Services', 'clinic.content.services.index', 'squares-2x2', $content, 'clinic.content.services.*'),
                    self::item('Blog', 'clinic.content.blog.index', 'newspaper', $content, 'clinic.content.blog.*'),
                    self::item('Pages', 'clinic.content.pages.index', 'document-text', $content, 'clinic.content.pages.*'),
                    self::item('Gallery', 'clinic.content.gallery.index', 'photo', $content, 'clinic.content.gallery.*'),
                    self::item('Testimonials', 'clinic.content.testimonials.index', 'chat-bubble-left-right', $content),
                    self::item('FAQs', 'clinic.content.faqs.index', 'question-mark-circle', $content),
                    self::item('Media library', 'clinic.content.media.index', 'rectangle-stack', $content),
                    self::item('Messages', 'clinic.content.messages.index', 'envelope', $content, null,
                        $content ? ContactMessage::whereNull('handled_at')->count() : null),
                ],
            ],
        ];

        $visible = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter($group['items']));

            if ($items !== []) {
                $visible[] = [...$group, 'items' => $items];
            }
        }

        return $visible;
    }

    /**
     * @return array{label: string, route: string, icon: string, active: string, badge: ?int}|null
     */
    private static function item(string $label, string $route, string $icon, bool $allowed, ?string $active = null, ?int $badge = null): ?array
    {
        return $allowed
            ? ['label' => $label, 'route' => $route, 'icon' => $icon, 'active' => $active ?? $route, 'badge' => $badge ?: null]
            : null;
    }
}
