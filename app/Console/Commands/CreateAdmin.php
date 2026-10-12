<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\RolesAndPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first administrator on a fresh production database, where no
 * seeder runs. The password is asked for interactively so it never ends up in
 * shell history. The admin still has to set up two-factor authentication at
 * first sign-in before the clinic dashboard opens.
 */
class CreateAdmin extends Command
{
    protected $signature = 'clinic:create-admin {email} {--name=Administrator}';

    protected $description = 'Create a clinic administrator account';

    public function handle(): int
    {
        $data = [
            'name' => (string) $this->option('name'),
            'email' => strtolower(trim((string) $this->argument('email'))),
            'password' => (string) $this->secret('Password'),
        ];
        $data['password_confirmation'] = (string) $this->secret('Confirm password');

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // The admin role must exist; this is the same idempotent sync `migrate` runs.
        RolesAndPermissions::sync();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            // Created by someone with server access, so the address counts as verified.
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(Role::Admin->value);
        });

        $this->info("Administrator {$data['email']} created. Sign in and set up two-factor authentication to open the clinic dashboard.");

        return self::SUCCESS;
    }
}
