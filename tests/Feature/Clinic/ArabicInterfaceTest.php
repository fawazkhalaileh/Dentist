<?php

namespace Tests\Feature\Clinic;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArabicInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionists_see_patient_record_labels_in_arabic(): void
    {
        $receptionist = User::factory()->receptionist()->withTwoFactor()->create(['locale' => 'ar']);
        $patient = Patient::factory()->create(['first_name' => 'Arabic', 'last_name' => 'Patient']);

        $this->actingAs($receptionist)
            ->get(route('clinic.patients.show', $patient))
            ->assertOk()
            ->assertSee('Arabic Patient')
            ->assertSee('lang="ar"', escape: false)
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee('البيانات الشخصية')
            ->assertSee('تاريخ الميلاد')
            ->assertSee('فريق الرعاية')
            ->assertSee('ربط الحساب')
            ->assertDontSee('Personal details')
            ->assertDontSee('Date of birth')
            ->assertDontSee('Care team')
            ->assertDontSee('Link account');
    }
}
