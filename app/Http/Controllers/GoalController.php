<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function index(): View
    {
        return view('goals.index', ['goals' => Goal::query()->orderByRaw('target_date is null')->orderBy('target_date')->get()]);
    }

    public function create(): View
    {
        return view('goals.form', ['goal' => new Goal(['color' => '#0ea5e9'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Goal::create($this->validated($request));

        return redirect()->route('goals.index')->with('success', 'Meta criada.');
    }

    public function edit(Goal $goal): View
    {
        return view('goals.form', compact('goal'));
    }

    public function update(Request $request, Goal $goal): RedirectResponse
    {
        $goal->update($this->validated($request));

        return redirect()->route('goals.index')->with('success', 'Meta atualizada.');
    }

    public function contribute(Request $request, Goal $goal): RedirectResponse
    {
        $this->normalizeMoney($request, 'value');
        $data = $request->validate(['value' => ['required', 'numeric', 'not_in:0']]);

        $goal->update(['current_amount' => max(0, (float) $goal->current_amount + (float) $data['value'])]);

        return back()->with('success', 'Valor da meta atualizado.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $goal->delete();

        return redirect()->route('goals.index')->with('success', 'Meta excluída.');
    }

    private function validated(Request $request): array
    {
        $this->normalizeMoney($request, 'target_amount', 'current_amount');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['current_amount'] ??= 0;

        return $data;
    }
}
