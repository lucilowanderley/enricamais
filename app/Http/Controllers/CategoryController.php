<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->whereNull('parent_id')->with('children')->withCount('transactions')->orderBy('name')->get();

        return view('categories.index', [
            'income' => $categories->where('type', CategoryType::Income),
            'expense' => $categories->where('type', CategoryType::Expense),
        ]);
    }

    public function create(Request $request): View
    {
        return view('categories.form', [
            'category' => new Category(['type' => $request->query('type', 'expense'), 'color' => '#64748b', 'parent_id' => $request->query('parent')]),
            'parents' => Category::query()->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Categoria criada.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereNull('parent_id')->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoria excluída. Os lançamentos ficaram sem categoria.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => ['nullable', 'integer'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if (! empty($data['parent_id'])) {
            $parent = Category::query()->whereNull('parent_id')->find($data['parent_id']);
            abort_if(! $parent || $parent->id === $category?->id, 422, 'Categoria principal inválida.');
            $data['type'] = $parent->type->value;
            if ($category && $category->children()->exists()) {
                abort(422, 'Uma categoria com subcategorias não pode virar subcategoria.');
            }
        } else {
            $data['parent_id'] = null;
        }

        return $data;
    }
}
