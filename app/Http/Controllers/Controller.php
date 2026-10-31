<?php

namespace App\Http\Controllers;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

abstract class Controller
{
    /** Normaliza campos monetários digitados no formato brasileiro antes da validação. */
    protected function normalizeMoney(Request $request, string ...$fields): void
    {
        $values = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $values[$field] = Money::parse($request->input($field));
            }
        }
        $request->merge($values);
    }

    /** Lê o parâmetro "month" (AAAA-MM) e retorna o 1º dia do mês; usa o mês atual se ausente ou inválido. */
    protected function month(Request $request): Carbon
    {
        $value = (string) $request->input('month', '');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return Carbon::createFromFormat('!Y-m', $value);
        }

        return now()->startOfMonth();
    }
}
