<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public static function inputs(): array
    {
        return [
            ['1.234,56', '1234.56'],
            ['R$ 10,5', '10.5'],
            ['1234.56', '1234.56'],
            ['1,234.56', '1234.56'],
            ['1.234.567', '1234567'],
            ['-50,00', '-50.00'],
            ['100', '100'],
        ];
    }

    #[DataProvider('inputs')]
    public function test_parses_brazilian_and_international_formats(string $input, string $expected): void
    {
        $this->assertSame($expected, Money::parse($input));
    }

    public function test_formats_brl(): void
    {
        $this->assertSame('R$ 1.234,56', Money::format(1234.56));
        $this->assertSame('-R$ 10,00', Money::format(-10));
    }
}
