<?php

namespace Tests\Feature\Clinic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_sees_only_the_guides_for_what_it_can_do(): void
    {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
            ->get(route('clinic.help'))->assertOk()
            ->assertSee('Setting up the clinic')->assertSee('Booking an appointment')->assertSee('Charting and treatments');

        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create())
            ->get(route('clinic.help'))->assertOk()
            ->assertSee('Booking an appointment')->assertSee('Requests from the website')
            ->assertDontSee('Setting up the clinic')->assertDontSee('Charting and treatments');

        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create())
            ->get(route('clinic.help'))->assertOk()
            ->assertSee('Charting and treatments')
            ->assertDontSee('Booking an appointment')->assertDontSee('Setting up the clinic');
    }

    public function test_patients_cannot_open_the_staff_help(): void
    {
        $this->actingAs(User::factory()->patient()->create())->get(route('clinic.help'))->assertForbidden();
    }
}
