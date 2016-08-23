<?php

namespace Gerardojbaez\LaraPlans\Models;

use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Period;
use Gerardojbaez\LaraPlans\Feature;
use Illuminate\Database\Eloquent\Model;
use Gerardojbaez\LaraPlans\Models\PlanFeature;
use Gerardojbaez\LaraPlans\Traits\BelongsToPlan;
use Gerardojbaez\LaraPlans\Contracts\PlanInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriptionInterface;
use Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException;
use Gerardojbaez\LaraPlans\Exceptions\FeatureValueFormatIncompatibleException;

class PlanSubscription extends Model implements PlanSubscriptionInterface
{
    use BelongsToPlan;

    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_ENDED = 'ended';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'trial_end',
        'current_period_end',
        'current_period_start',
        'canceled_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'canceled_at', 'trial_end',
        'current_period_start', 'current_period_end'
    ];

    /**
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function($model)
        {
            // Set period if isn't set
            if (!$model->current_period_start OR !$model->current_period_start)
                $model->setNewPeriod();
        });
    }

    /**
     * Get user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get subscription usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage()
    {
        return $this->hasMany(config('laraplans.models.plan_subscription_usage'), 'subscription_id');
    }

    /**
     * Get status attribute.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->isActive())
            return self::STATUS_ACTIVE;

        if ($this->isCanceled())
            return self::STATUS_CANCELED;

        if ($this->periodEnded())
            return self::STATUS_ENDED;
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->isCanceled())
            return false;

        if ($this->isTrialling())
            return true;

        if ($this->periodEnded())
            return false;

        return true;
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function periodEnded()
    {
        if ($this->current_period_end->isToday() OR $this->current_period_end->isPast())
            return true;

        return false;
    }

    /**
     * Check if subscription is trialling.
     *
     * @return bool
     */
    public function isTrialling()
    {
        if (!is_null($this->trial_end) AND $this->trial_end->isFuture())
            return true;

        return false;
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function isCanceled()
    {
        if (is_null($this->canceled_at))
            return false;

        return ($this->canceled_at->isToday() OR $this->canceled_at->isPast());
    }

    /**
     * Cancel subscription.
     *
     * @param  bool $at_period_end
     * @return $this
     */
    public function cancel($immediately = false)
    {
        if ($immediately)
            $this->canceled_at = new Carbon;
        else
            $this->canceled_at = $this->current_period_end;

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param mixed $plan Plan Id or Plan Model Instance
     * @return $this
     */
    public function changePlan($plan)
    {
        if (is_numeric($plan))
            $plan = App::make(PlanInterface::class)->find($plan);

        // If plans doesn't have the same billing frequency (e.g., interval
        // and interval_count) we will update the billing dates starting
        // today... and sice we are basically creating a new billing cycle,
        // the usage data will be cleared.
        if (is_null($this->plan) OR $this->plan->interval !== $plan->interval OR
                $this->plan->interval_count !== $plan->interval_count)
        {
            // Set period
            $this->setNewPeriod($plan->interval, $plan->interval_count);

            // Clear usage data
            $this->clearUsage();
        }

        // Attach new plan to subscription
        $this->plan_id = $plan->id;

        // Refresh relations
        $this->load('plan');

        return $this;
    }

    /**
     * Check whether limit was reached or not.
     *
     * @param string $feature_code
     * @throws \Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException
     * @throws \Gerardojbaez\LaraPlans\Exceptions\FeatureValueFormatIncompatibleException
     * @return boolean
     */
    public function limitReached($feature_code)
    {
        // Get features and usage
        $feature = $this->getFeatureByCode($feature_code);

        if (!$feature)
            throw new InvalidPlanFeatureException($feature_code);

        // Match "booleans" type value
        if ($this->featureEnabled($feature_code) === true)
            return false;

        // If the feature is zero, let's return true since there's no uses
        // available. (useful to disable countable features)
        if ($feature->value === '0')
            return true;

        // Get feature usage data to check for expiration and
        // remaining uses...
        $usage = $this->usage->where('code', $feature->code)->first();

        // Feature has usage record?
        if (!$usage)
            return false;

        // Usage has expired?
        if ($usage->isExpired() === true)
            return false;

        // Feature has remaining uses?
        if (($feature->value - $usage->used) > 0)
            return false;

        return true;
    }

    /**
     * Check if subscription plan feature is enabled.
     *
     * @param string $feature_code
     * @throws \Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException
     * @return bool
     */
    public function featureEnabled($feature_code)
    {
        $feature = $this->getFeatureByCode($feature_code);

        if (!$feature)
            return false;

        // If value is one of the positive words configured then the
        // feature is enabled.
        if (in_array(strtoupper($feature->value), config('laraplans.positive_words')))
            return true;

        return false;
    }

    /**
     * Record usage.
     *
     * This will create or update a usage record.
     *
     * @param string $feature_code
     * @param int $uses
     * @return \Gerardojbaez\LaraPlans\Models\PlanSubscriptionUsage
     */
    public function recordUsage($feature_code, $uses = 1)
    {
        $feature = new Feature($feature_code);

        $usage = $this->usage()->firstOrNew([
            'code' => $feature_code,
        ]);

        if($feature->isReseteable())
        {
            // Is 'valid_until' attribute null?
            if (is_null($usage->valid_until))
            {
                $usage->valid_until = $feature->getResetDate($this->created_at);
            }

            // Has expired?
            elseif ($usage->isExpired() === true)
            {
                $usage->valid_until = $feature->getResetDate($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used += $uses;

        $usage->save();

        // Refresh usage records
        $this->load('usage');

        return $usage;
    }

    /**
     * Get feature's value.
     *
     * Useful when you need to set model attribute
     * based on a plan's feature's value.
     */
    public function getFeatureValue($feature_code, $default = null)
    {
        $feature = $this->plan->features->where('code', $feature_code)->first();

        if (!$feature)
            return $default;

        return $feature->value;
    }

    /**
     * Clear usage data.
     *
     * @return $this
     */
    public function clearUsage()
    {
        $this->usage()->delete();

        if ($this->relationLoaded('usage'))
            $this->load('usage');

        return $this;
    }

    /**
     * Set new subscription period.
     *
     * @param  string $interval
     * @param  int $interval_count
     * @param  string $start Start date
     * @return  $this
     */
    public function setNewPeriod($interval = '', $interval_count = '', $start = '')
    {
        if (empty($interval))
            $interval = $this->plan->interval;

        if (empty($interval_count))
            $interval_count = $this->plan->interval_count;

        $period = new Period($interval, $interval_count, $start);

        $this->current_period_start = $period->getStartDate();
        $this->current_period_end = $period->getEndDate();

        return $this;
    }

    /**
     * Get feature from subscription plan.
     *
     * @return \Gerardojbaez\LaraPlans\Models\PlanFeature|null
     */
    protected function getFeatureByCode($code)
    {
        return $this->plan->features->where('code', $code)->first();
    }

    /**
     * Find by user id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $user_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function scopeByUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    /**
     * Find subscription with an ending trial.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingTrial($query, $dayRange = 3)
    {
        $from = new Carbon;
        $to = (new Carbon)->addDays($dayRange);

        $query->whereBetween('trial_end', [$from, $to]);
    }

    /**
     * Find subscription with an ended trial.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial($query)
    {
        $query->where('trial_end', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Find ending subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod($query, $dayRange = 3)
    {
        $from = new Carbon;
        $to = (new Carbon)->addDays($dayRange);

        $query->whereBetween('current_period_end', [$from, $to]);
    }

    /**
     * Find ended subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod($query)
    {
        $query->where('current_period_end', '<=', date('Y-m-d H:i:s'));
    }
}
