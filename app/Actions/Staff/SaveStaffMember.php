<?php

namespace App\Actions\Staff;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\Role;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a staff account (role, active flag) and a dentist's public profile.
 *
 * Guards: administrators cannot demote or deactivate themselves, and the clinic
 * always keeps at least one active administrator.
 */
class SaveStaffMember
{
    use SavesTranslatedContent;

    /**
     * @param  array{
     *     name: string, email: string, role: string, is_active: bool, locale: string,
     *     profile: array{is_public: bool, slug: string, license_number: ?string, photo_media_id: ?int,
     *         sort_order: int, translations: array<string, array<string, ?string>>}|null
     * }  $data
     */
    public function handle(array $data, User $actor, ?User $user = null): User
    {
        $role = Role::from($data['role']);
        abort_unless($role->isStaff(), 422);

        if ($user) {
            $this->guardAdministrators($user, $role, $data['is_active'], $actor);
        }

        return DB::transaction(function () use ($data, $role, $user) {
            $isNew = $user === null;
            $user ??= new User;
            $previousRole = $user->roles->first()->name ?? null;
            $wasActive = $user->exists ? $user->is_active : true;

            $user->fill(['name' => $data['name'], 'email' => $data['email'], 'locale' => $data['locale']]);
            $user->is_active = $data['is_active'];

            if ($isNew) {
                // No usable password: the person chooses one through a setup link.
                $user->password = Str::password(64);
                // Added by an administrator who knows the person, so the address counts as verified.
                $user->email_verified_at = now();
            }

            if ($user->isDirty('email') && ! $isNew) {
                $user->email_verified_at = now();
            }

            $user->save();
            $user->syncRoles([$role->value]);

            if ($wasActive && ! $user->is_active) {
                $this->endSessions($user);
            }

            $this->saveProfile($user, $role, $data['profile']);

            Audit::record($isNew ? 'staff.created' : 'staff.updated', $user, array_filter([
                'role' => $role->value,
                'previous_role' => $previousRole !== $role->value ? $previousRole : null,
                'deactivated' => $wasActive && ! $user->is_active ? true : null,
                'reactivated' => ! $wasActive && $user->is_active ? true : null,
            ], fn ($v) => $v !== null));

            return $user->load(['roles', 'staffProfile.translations']);
        });
    }

    private function guardAdministrators(User $user, Role $role, bool $active, User $actor): void
    {
        if ($user->is($actor) && ($role !== Role::Admin || ! $active)) {
            throw ValidationException::withMessages([
                'form.role' => __('You cannot remove your own administrator access or deactivate your own account.'),
            ]);
        }

        $losesAdmin = $user->hasRoleEnum(Role::Admin) && $user->is_active && ($role !== Role::Admin || ! $active);
        $otherActiveAdmins = User::havingRole(Role::Admin)->where('is_active', true)->whereKeyNot($user->id)->count();

        if ($losesAdmin && $otherActiveAdmins === 0) {
            throw ValidationException::withMessages([
                'form.role' => __('The clinic needs at least one active administrator.'),
            ]);
        }
    }

    /**
     * @param  array{is_public: bool, slug: string, license_number: ?string, photo_media_id: ?int,
     *     sort_order: int, translations: array<string, array<string, ?string>>}|null  $profile
     */
    private function saveProfile(User $user, Role $role, ?array $profile): void
    {
        $existing = $user->staffProfile;

        // A profile only appears publicly for dentists; keep its text if the role changes.
        if ($role !== Role::Dentist || $profile === null) {
            $existing?->update(['is_public' => false]);

            return;
        }

        $model = $existing ?? new StaffProfile(['user_id' => $user->id]);
        $model->fill([
            'slug' => $profile['slug'],
            'license_number' => $profile['license_number'],
            'photo_media_id' => $profile['photo_media_id'],
            'is_public' => $profile['is_public'],
            'sort_order' => $profile['sort_order'],
        ])->save();

        $this->syncTranslations($model->translations(), $profile['translations'], titleKey: 'display_name', withSlug: false);
    }

    /**
     * Sign a deactivated person out everywhere straight away, not only on their next request.
     */
    private function endSessions(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
