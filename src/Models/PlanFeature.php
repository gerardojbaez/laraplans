<?php

namespace Gerardojbaez\Laraplans\Models;

use Gerardojbaez\Laraplans\Contracts\PlanFeatureInterface;
use Gerardojbaez\Laraplans\Traits\BelongsToPlan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at'
    ];

    /**
     * Get feature usage.
     *
     * This will return all related
     * subscriptions usages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage()
    {
        return $this->hasMany(config('laraplans.models.plan_subscription_usage'));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Gerardojbaez\Laraplans\Database\Factories\PlanFeatureFactory::new();
    }
}
