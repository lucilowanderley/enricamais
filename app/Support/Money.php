<?php

namespace App\Support;

class Money
{
    /**
     * Converte valores digitados no padrão brasileiro ("1.234,56", "R$ 10,5") ou
     * internacional ("1234.56") para string numérica com ponto decimal.
     */
    public static function parse(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = trim(str_replace(['R$', ' ', "\u{00A0}"], '', (string) $value));
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-+');

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            // vírgula é o separador decimal
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($lastDot !== false && $lastComma !== false) {
            // ponto é o separador decimal, vírgula é milhar
            $value = str_replace(',', '', $value);
        } elseif ($lastDot !== false && substr_count($value, '.') > 1) {
            // "1.234.567" => milhar
            $value = str_replace('.', '', $value);
        }

        if (! is_numeric($value)) {
            return $value; // deixa a validação reprovar
        }

        return ($negative ? '-' : '').$value;
    }

    public static function format(float|int|string|null $value, string $currency = 'BRL'): string
    {
        $symbol = match ($currency) {
            'USD' => 'US$',
            'EUR' => '€',
            default => 'R$',
        };

        $value = (float) $value;
        $formatted = number_format(abs($value), 2, ',', '.');

        return ($value < 0 ? '-' : '').$symbol.' '.$formatted;
    }

    public static function input(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, ',', '.');
    }
}
