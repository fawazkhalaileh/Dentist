<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function amounts(): array
    {
        return [
            'whole dinars' => ['25', 25000],
            'one decimal' => ['25.5', 25500],
            'three decimals' => ['12.125', 12125],
            'comma as decimal point' => ['25,5', 25500],
            'arabic digits' => ['٣٠', 30000],
            'zero' => ['0', 0],
        ];
    }

    #[DataProvider('amounts')]
    public function test_typed_amounts_become_fils(string $input, int $fils): void
    {
        $this->assertSame($fils, Money::parse($input));
    }

    public function test_invalid_amounts_are_rejected(): void
    {
        foreach (['-5', 'abc', '1.2345', '', '1.2.3'] as $bad) {
            try {
                Money::parse($bad);
                $this->fail("{$bad} should be rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_amounts_are_formatted_for_people(): void
    {
        $this->assertSame('25 JD', Money::format(25000, 'en'));
        $this->assertSame('25.500 JD', Money::format(25500, 'en'));
        $this->assertSame('1,250 د.أ', Money::format(1250000, 'ar'));
        $this->assertSame('–', Money::format(null));
        $this->assertSame('25.500', Money::plain(25500));
        $this->assertSame('25', Money::plain(25000));
        $this->assertSame('', Money::plain(null));
    }
}
