<?php

namespace Tests\Feature\Clinic;

use App\Models\Patient;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityLandmarksTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_page_has_one_main_landmark_and_a_skip_link_target(): void
    {
        $staff = User::factory()->admin()->withTwoFactor()->create();
        $patient = Patient::factory()->create();
        $this->actingAs($staff);

        foreach (['overview', 'treatments', 'history', 'notes', 'documents', 'appointments', 'billing'] as $tab) {
            $response = $this->get(route('clinic.patients.show', ['patient' => $patient, 'tab' => $tab]))
                ->assertOk();

            $document = new DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new DOMXPath($document);

            $this->assertSame(1, $xpath->query('//main | //*[@role="main"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="main-content"]')->length);
            $this->assertSame(1, $xpath->query('//main[@id="main-content"] | //*[@role="main" and @id="main-content"]')->length);
            $this->assertGreaterThan(0, $xpath->query('//a[@href="#main-content"]')->length);
            $this->assertGreaterThan(0, $xpath->query('//nav[@data-flux-sidebar-nav] | //*[@role="navigation" and @data-flux-sidebar-nav]')->length);
        }
    }
}
