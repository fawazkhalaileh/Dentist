<?php

namespace Tests\Unit;

use App\Enums\Dentition;
use App\Enums\TreatmentStatus;
use App\Support\Fdi;
use PHPUnit\Framework\TestCase;

class FdiTest extends TestCase
{
    public function test_permanent_dentition_is_32_teeth_from_11_to_48(): void
    {
        $teeth = Fdi::permanent();

        $this->assertCount(32, $teeth);
        $this->assertSame(11, $teeth[0]);
        $this->assertSame(48, $teeth[31]);
        $this->assertNotContains(19, $teeth);
    }

    public function test_primary_dentition_is_20_teeth_from_51_to_85(): void
    {
        $teeth = Fdi::primary();

        $this->assertCount(20, $teeth);
        $this->assertSame(51, $teeth[0]);
        $this->assertSame(85, $teeth[19]);
        $this->assertNotContains(56, $teeth);
    }

    public function test_tooth_attributes(): void
    {
        $this->assertSame('upper', Fdi::arch(11));
        $this->assertSame('lower', Fdi::arch(36));
        $this->assertSame(Dentition::Primary, Fdi::dentition(75));
        $this->assertSame('Lower left first molar', Fdi::name(36));
        $this->assertFalse(Fdi::isValid(10));
        $this->assertFalse(Fdi::isValid(49));
    }

    public function test_treatment_status_transitions(): void
    {
        $this->assertTrue(TreatmentStatus::Planned->canTransitionTo(TreatmentStatus::Completed));
        $this->assertTrue(TreatmentStatus::InProgress->canTransitionTo(TreatmentStatus::Cancelled));
        $this->assertFalse(TreatmentStatus::Completed->canTransitionTo(TreatmentStatus::Planned));
        $this->assertFalse(TreatmentStatus::InProgress->canTransitionTo(TreatmentStatus::Planned));
        $this->assertTrue(TreatmentStatus::Cancelled->isFinal());
    }
}
