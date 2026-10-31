<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class WorkspaceService
{
    public function create(User $owner, string $name, WorkspaceType $type, string $currency = 'BRL', ?string $document = null): Workspace
    {
        return DB::transaction(function () use ($owner, $name, $type, $currency, $document) {
            $workspace = Workspace::create([
                'name' => $name,
                'type' => $type,
                'currency' => $currency,
                'document' => $document,
                'owner_id' => $owner->id,
            ]);

            $workspace->members()->attach($owner->id, ['role' => WorkspaceRole::Owner->value]);

            $this->seedCategories($workspace);

            Account::create([
                'workspace_id' => $workspace->id,
                'name' => $type === WorkspaceType::Business ? 'Conta empresa' : 'Carteira',
                'type' => $type === WorkspaceType::Business ? AccountType::Checking : AccountType::Cash,
                'initial_balance' => 0,
                'color' => '#16a34a',
            ]);

            if (! $owner->current_workspace_id) {
                $owner->forceFill(['current_workspace_id' => $workspace->id])->save();
            }

            return $workspace;
        });
    }

    public function seedCategories(Workspace $workspace): void
    {
        foreach (DefaultCategories::for($workspace->type) as $type => $groups) {
            foreach ($groups as $name => $data) {
                $parent = Category::create([
                    'workspace_id' => $workspace->id,
                    'name' => $name,
                    'type' => $type,
                    'color' => $data['color'],
                ]);
                foreach ($data['children'] ?? [] as $child) {
                    Category::create([
                        'workspace_id' => $workspace->id,
                        'parent_id' => $parent->id,
                        'name' => $child,
                        'type' => $type,
                        'color' => $data['color'],
                    ]);
                }
            }
        }
    }
}
