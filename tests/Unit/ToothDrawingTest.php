<?php

namespace Tests\Unit;

use App\Models\Procedure;
use App\Support\ToothDrawing;
use PHPUnit\Framework\TestCase;

class ToothDrawingTest extends TestCase
{
    public function test_each_tooth_gets_the_shape_of_its_type(): void
    {
        $this->assertSame('incisor', ToothDrawing::type(11));
        $this->assertSame('incisor', ToothDrawing::type(42));
        $this->assertSame('canine', ToothDrawing::type(23));
        $this->assertSame('premolar', ToothDrawing::type(35));
        $this->assertSame('molar', ToothDrawing::type(16));
        $this->assertSame('molar', ToothDrawing::type(48));
        // Children have no premolars: positions 4 and 5 are molars.
        $this->assertSame('molar', ToothDrawing::type(54));
        $this->assertSame('canine', ToothDrawing::type(83));
    }

    public function test_whole_tooth_procedures_choose_how_they_are_drawn(): void
    {
        $procedure = fn (string $code, string $category) => new Procedure(['code' => $code, 'category' => $category]);

        $this->assertSame('extraction', ToothDrawing::marker($procedure('EXT-SURG', 'surgical')));
        $this->assertSame('extraction', ToothDrawing::marker($procedure('WISDOM', 'surgical')));
        $this->assertSame('implant', ToothDrawing::marker($procedure('IMPL', 'surgical')));
        $this->assertSame('canal', ToothDrawing::marker($procedure('RCT', 'endodontic')));
        $this->assertSame('crown', ToothDrawing::marker($procedure('CROWN', 'prosthodontic')));
        $this->assertSame('tint', ToothDrawing::marker($procedure('SEAL', 'preventive')));
        $this->assertSame('tint', ToothDrawing::marker($procedure('CUSTOM', 'other')));
    }

    public function test_the_five_surfaces_cover_the_top_view_box(): void
    {
        $regions = ToothDrawing::regions([0, 0, 40, 40], ToothDrawing::innerArea('molar'));

        $this->assertSame(['top', 'bottom', 'left', 'right'], array_keys($regions));
        $this->assertStringStartsWith('0,0 40,0', $regions['top']);
    }

    public function test_childrens_teeth_reuse_the_matching_adult_render(): void
    {
        $this->assertSame(16, ToothDrawing::imageTooth(16));
        $this->assertSame(11, ToothDrawing::imageTooth(51));
        $this->assertSame(23, ToothDrawing::imageTooth(63));
        $this->assertSame(36, ToothDrawing::imageTooth(74));
        $this->assertSame(47, ToothDrawing::imageTooth(85));
    }
}
