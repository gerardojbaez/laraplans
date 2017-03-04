<?php

namespace Gerardojbaez\LaraPlans\Models;

use DB;
use App;
use Carbon\Carbon;
use LogicException;
use Gerardojbaez\LaraPlans\Period;
use Illuminate\Database\Eloquent\Model;
use Gerardojbaez\LaraPlans\Models\PlanFeature;
use Gerardojbaez\LaraPlans\SubscriptionAbility;
use Gerardojbaez\LaraPlans\SubscriptionUsageManager;
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'name',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
        'canceled_at', 'trial_ends_at', 'ends_at', 'starts_at'
    ];

    /**
     * Subscription Ability Manager instance.
     *
     * @var Gerardojbaez\LaraPlans\SubscriptionAbility
     */
    protected $ability;

    /**
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Set period if it wasn't set
            if (! $model->ends_at) {
                $model->setNewPeriod();
            }
        });
    }

    /**
     * Get user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
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
        return $this->hasMany(
            config('laraplans.models.plan_subscription_usage'),
            'subscription_id'
        );
    }

    /**
     * Get status attribute.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->active()) {
            return self::STATUS_ACTIVE;
        }

        if ($this->canceled()) {
            return self::STATUS_CANCELED;
        }
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        if (! $this->ended() or $this->onTrial()) {
            return true;
        }

        return false;
    }

    /**
     * Check if subscription is trialling.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (! is_null($trialEndsAt = $this->trial_ends_at)) {
            return Carbon::now()->lt(Carbon::instance($trialEndsAt));
        }

        return false;
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function canceled()
    {
        return  ! is_null($this->canceled_at);
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function ended()
    {
        $endsAt = Carbon::instance($this->ends_at);

        return Carbon::now()->gt($endsAt) or Carbon::now()->eq($endsAt);
    }

    /**
     * Cancel subscription.
     *
     * @param  bool $immediately
     * @return $this
     */
    public function cancel($immediately = false)
    {
        $this->canceled_at = Carbon::now();

        if ($immediately) {
            $this->ends_at = $this->canceled_at;
        }

        $this->save();

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
        if (is_numeric($plan)) {
            $plan = App::make(PlanInterface::class)->find($plan);
        }

        // If plans doesn't have the same billing frequency (e.g., interval
        // and interval_count) we will update the billing dates starting
        // today... and sice we are basically creating a new billing cycle,
        // the usage data will be cleared.
        if (is_null($this->plan) or $this->plan->interval !== $plan->interval or
                $this->plan->interval_count !== $plan->interval_count) {
            // Set period
            $this->setNewPeriod($plan->interval, $plan->interval_count);

            // Clear usage data
            $usageManager = new SubscriptionUsageManager($this);
            $usageManager->clear();
        }

        // Attach new plan to subscription
        $this->plan_id = $plan->id;

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @throws  \LogicException
     * @return  $this
     */
    public function renew()
    {
        if ($this->ended() and $this->canceled()) {
            throw new LogicException(
                'Unable to renew canceled ended subscription.'
            );
        }

        $subscription = $this;

        DB::transaction(function () use ($subscription) {
            // Clear usage data
            $usageManager = new SubscriptionUsageManager($subscription);
            $usageManager->clear();

            // Renew period
            $subscription->setNewPeriod();
            $subscription->canceled_at = null;
            $subscription->save();
        });

        return $this;
    }

    /**
     * Get Subscription Ability instance.
     *
     * @return \Gerardojbaez\LaraPlans\SubscriptionAbility
     */
    public function ability()
    {
        if (is_null($this->ability)) {
            return new SubscriptionAbility($this);
        }

        return $this->ability;
    }

    /**
     * Find by user id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $user_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, $user_id)
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
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        $query->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Find subscription with an ended trial.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial($query)
    {
        $query->where('trial_ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Find ending subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod($query, $dayRange = 3)
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        $query->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Find ended subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod($query)
    {
        $query->where('ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Set subscription period.
     *
     * @param  string $interval
     * @param  int $interval_count
     * @param  string $start Start date
     * @return  $this
     */
    protected function setNewPeriod($interval = '', $interval_count = '', $start = '')
    {
        if (empty($interval)) {
            $interval = $this->plan->interval;
        }

        if (empty($interval_count)) {
            $interval_count = $this->plan->interval_count;
        }

        $period = new Period($interval, $interval_count, $start);

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }
}
