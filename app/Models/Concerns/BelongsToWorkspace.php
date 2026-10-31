<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isola os dados por espaço: consultas são filtradas pelo espaço ativo e novos
 * registros recebem o workspace_id automaticamente.
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            $id = app(WorkspaceContext::class)->id();
            if ($id !== null) {
                $query->where($query->getModel()->qualifyColumn('workspace_id'), $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->workspace_id)) {
                $model->workspace_id = app(WorkspaceContext::class)->id();
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
