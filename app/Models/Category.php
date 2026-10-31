<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'parent_id', 'name', 'type', 'color'];

    protected function casts(): array
    {
        return ['type' => CategoryType::class];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->parent ? $this->parent->name.' › '.$this->name : $this->name;
    }
}
