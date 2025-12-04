<?php

namespace Gerardojbaez\Laraplans\Models;

use Gerardojbaez\Laraplans\Contracts\PlanFeatureInterface;
use Gerardojbaez\Laraplans\Database\Factories\PlanFeatureFactory;
use Gerardojbaez\Laraplans\Traits\BelongsToPlan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanFeature extends Model implements PlanFeatureInterface
{
    use BelongsToPlan, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_id',
        'code',
        'value',
        'sort_order'
    ];

    public function usage(): HasMany
    {
        return $this->hasMany(config('laraplans.models.plan_subscription_usage'));
    }

    protected static function newFactory(): PlanFeatureFactory
    {
        return PlanFeatureFactory::new();
    }
}
