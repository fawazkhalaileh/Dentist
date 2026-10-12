<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 changed the staff_profiles and services create migrations in place.
 * Databases migrated during Phase 1 never re-run those, so this brings them to
 * the same shape. On a fresh install every step is already satisfied and skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_profile_translations')) {
            Schema::create('staff_profile_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('display_name');
                $table->string('specialty')->nullable();
                $table->text('bio')->nullable();
                $table->timestamps();

                $table->unique(['staff_profile_id', 'locale']);
            });
        }

        if (Schema::hasColumn('staff_profiles', 'bio')) {
            // Carry Phase 1 profile text over as the default-locale translation.
            DB::table('staff_profiles')
                ->join('users', 'users.id', '=', 'staff_profiles.user_id')
                ->select('staff_profiles.*', 'users.name as user_name')
                ->orderBy('staff_profiles.id')
                ->each(function (object $profile) {
                    DB::table('staff_profile_translations')->insertOrIgnore([
                        'staff_profile_id' => $profile->id,
                        'locale' => Locales::default(),
                        'display_name' => trim(($profile->title ?? '').' '.$profile->user_name),
                        'specialty' => $profile->specialty,
                        'bio' => $profile->bio,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->dropColumn(['title', 'specialty', 'bio', 'photo_path']);
            });
        }

        if (! Schema::hasColumn('staff_profiles', 'photo_media_id')) {
            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->foreignId('photo_media_id')->nullable()->after('license_number')->constrained('media')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('services', 'featured_image_path')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('featured_image_path');
            });
        }

        if (! Schema::hasColumn('services', 'featured_media_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->foreignId('featured_media_id')->nullable()->after('status')->constrained('media')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Forward-only: rolling back the Phase 2 create migrations removes these columns.
    }
};
