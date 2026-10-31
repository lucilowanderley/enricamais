<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToWorkspace, StoresDateOnly;

    protected $fillable = ['workspace_id', 'category_id', 'month', 'amount'];

    protected function casts(): array
    {
        return ['month' => 'date', 'amount' => 'decimal:2'];
    }

    protected function month(): Attribute
    {
        return static::dateOnlyAttribute();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
