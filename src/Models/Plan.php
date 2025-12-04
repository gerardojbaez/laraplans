<?php

namespace Gerardojbaez\Laraplans\Models;

use Gerardojbaez\Laraplans\Contracts\PlanInterface;
use Gerardojbaez\Laraplans\Database\Factories\PlanFactory;
use Gerardojbaez\Laraplans\Exceptions\InvalidPlanFeatureException;
use Gerardojbaez\Laraplans\Period;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model implements PlanInterface
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'interval',
        'interval_count',
        'trial_period_days',
        'sort_order',
    ];

    /**
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            if (!$model->interval) {
                $model->interval = 'month';
            }

            if (!$model->interval_count) {
                $model->interval_count = 1;
            }
        });
    }

    protected static function newFactory(): PlanFactory
    {
        return \Gerardojbaez\Laraplans\Database\Factories\PlanFactory::new();
    }

    public function features(): HasMany
    {
        return $this->hasMany(config('laraplans.models.plan_feature'));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('laraplans.models.plan_subscription'));
    }

    /**
     * Get Interval Name
     *
     * @return mixed string|null
     */
    public function getIntervalNameAttribute()
    {
        $intervals = Period::getAllIntervals();
        return (isset($intervals[$this->interval]) ? $intervals[$this->interval] : null);
    }

    /**
     * Get Interval Description
     *
     * @return string
     */
    public function getIntervalDescriptionAttribute()
    {
        return trans_choice('laraplans::messages.interval_description.' . $this->interval, $this->interval_count);
    }

    public function isFree(): bool
    {
        return ((float)$this->price <= 0.00);
    }

    public function hasTrial(): bool
    {
        return (is_numeric($this->trial_period_days) and $this->trial_period_days > 0);
    }

    /**
     * Returns the demanded feature
     *
     * @param string $code
     * @return PlanFeature
     * @throws InvalidPlanFeatureException
     */
    public function getFeatureByCode(string $code): PlanFeature
    {
        $feature = $this->features()->getEager()->firstWhere('code', $code);

        if (is_null($feature)) {
            throw new InvalidPlanFeatureException($code);
        }

        return $feature;
    }
}
