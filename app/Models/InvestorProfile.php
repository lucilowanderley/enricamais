<?php

namespace App\Models;

use App\Enums\AssetClass;
use App\Enums\RiskProfile;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class InvestorProfile extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'risk_profile', 'horizon_years', 'monthly_contribution',
        'emergency_fund_months', 'objectives', 'target_allocation',
    ];

    protected function casts(): array
    {
        return [
            'risk_profile' => RiskProfile::class,
            'monthly_contribution' => 'decimal:2',
            'target_allocation' => 'array',
        ];
    }

    /** @return array<string, float> */
    public function targetAllocation(): array
    {
        $target = $this->target_allocation ?: AssetClass::suggestedAllocation($this->risk_profile ?? RiskProfile::Moderate);

        return array_filter(array_map('floatval', $target), fn ($v) => $v > 0);
    }
}
