<?php

namespace Gerardojbaez\Laraplans\Models;

use Carbon\Carbon;
use Gerardojbaez\Laraplans\Contracts\PlanSubscriptionUsageInterface;
use Gerardojbaez\Laraplans\Database\Factories\PlanSubscriptionUsageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanSubscriptionUsage extends Model implements PlanSubscriptionUsageInterface
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subscription_id',
        'code',
        'valid_until',
        'used'
    ];

    protected $casts = [
        'used' => 'integer',
        'valid_until' => 'datetime',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('laraplans.models.plan_feature'));
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('laraplans.models.plan_subscription'));
    }

    public function scopeByFeatureCode(Builder $query, string $feature_code): Builder
    {
        return $query->whereCode($feature_code);
    }

    public function isExpired(): bool
    {
        if (is_null($this->valid_until)) {
            return false;
        }

        return Carbon::now()->gte($this->valid_until);
    }

    protected static function newFactory(): PlanSubscriptionUsageFactory
    {
        return PlanSubscriptionUsageFactory::new();
    }
}
