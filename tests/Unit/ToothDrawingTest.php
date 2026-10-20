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
        $top = ToothDrawing::top('molar');
        $regions = ToothDrawing::regions($top['box'], $top['inner']);

        $this->assertSame(['top', 'bottom', 'left', 'right'], array_keys($regions));
        $this->assertStringStartsWith('2.5,3.5 37.5,3.5', $regions['top']);
    }
}
