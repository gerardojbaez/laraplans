<?php

namespace Gerardojbaez\Laraplans\Models;

use Carbon\Carbon;
use Gerardojbaez\Laraplans\Contracts\PlanInterface;
use Gerardojbaez\Laraplans\Contracts\PlanSubscriptionInterface;
use Gerardojbaez\Laraplans\Database\Factories\PlanSubscriptionFactory;
use Gerardojbaez\Laraplans\Events\SubscriptionCanceled;
use Gerardojbaez\Laraplans\Events\SubscriptionCreated;
use Gerardojbaez\Laraplans\Events\SubscriptionRenewed;
use Gerardojbaez\Laraplans\Events\SubscriptionSaved;
use Gerardojbaez\Laraplans\Events\SubscriptionSaving;
use Gerardojbaez\Laraplans\Exceptions\InvalidIntervalException;
use Gerardojbaez\Laraplans\Period;
use Gerardojbaez\Laraplans\SubscriptionAbility;
use Gerardojbaez\Laraplans\SubscriptionUsageManager;
use Gerardojbaez\Laraplans\Traits\BelongsToPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Throwable;

class PlanSubscription extends Model implements PlanSubscriptionInterface
{
    use BelongsToPlan, HasFactory;

    /**
     * Subscription statuses
     */
    const STATUS_ENDED = 'ended';
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_id',
        'name',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'canceled_immediately' => 'boolean',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'plan_id' => 'integer'
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => SubscriptionCreated::class,
        'saving' => SubscriptionSaving::class,
        'saved' => SubscriptionSaved::class,
    ];

    /**
     * Subscription Ability Manager instance.
     *
     * @var SubscriptionAbility|null
     */
    protected ?SubscriptionAbility $ability = null;

    public function subscribable(): BelongsTo
    {
        return $this->morphTo();
    }

    public function usage(): HasMany
    {
        return $this->hasMany(
            config('laraplans.models.plan_subscription_usage'),
            'subscription_id'
        );
    }

    protected static function newFactory(): PlanSubscriptionFactory
    {
        return PlanSubscriptionFactory::new();
    }

    /**
     * Get status attribute.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->isActive()) {
            return self::STATUS_ACTIVE;
        }

        if ($this->isCanceled()) {
            return self::STATUS_CANCELED;
        }

        if ($this->isEnded()) {
            return self::STATUS_ENDED;
        }
    }


    public function isActive(): bool
    {
        if ((!$this->isEnded() or $this->onTrial()) and !$this->isCanceledImmediately()) {
            return true;
        }

        return false;
    }

    public function onTrial(): bool
    {
        if (!is_null($trialEndsAt = $this->trial_ends_at)) {
            return Carbon::now()->lt(Carbon::instance($trialEndsAt));
        }

        return false;
    }

    public function isCanceled(): bool
    {
        return !is_null($this->canceled_at);
    }

    public function isCanceledImmediately(): bool
    {
        return (!is_null($this->canceled_at) and $this->canceled_immediately === true);
    }

    public function isEnded(): bool
    {
        $endsAt = Carbon::parse($this->ends_at);

        return Carbon::now()->gte($endsAt);
    }

    /**
     * Cancel subscription.
     *
     * @param bool $immediately
     */
    public function cancel($immediately = false): false|static
    {
        $this->canceled_at = Carbon::now();

        if ($immediately) {
            $this->canceled_immediately = true;
        }

        if ($this->save()) {
            event(new SubscriptionCanceled($this));
            return $this;
        }

        return false;
    }

    /**
     * Change subscription plan.
     *
     * @param int|Plan $plan Plan's ID or Plan Model Instance
     */
    public function changePlan(mixed $plan): static
    {
        if (is_numeric($plan)) {
            $plan = App::make(PlanInterface::class)->find($plan);
        }

        // Detect billing frequency changes between plans (based on the interval and
        // interval_count). If they differ, it will reset the billing cycle from
        // today and clear all usage data, since this effectively creates a new
        // subscription period.
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
     * @throws Throwable
     */
    public function renew(): static
    {
        // Clear usage data
        $usageManager = new SubscriptionUsageManager($this);
        $usageManager->clear();

        // Renew period
        $this->setNewPeriod();
        $this->canceled_at = null;
        $this->save();

        event(new SubscriptionRenewed($this));

        return $this;
    }

    /**
     * Get Subscription Ability instance.
     */
    public function ability(): SubscriptionAbility
    {
        if (!$this->ability) {
            $this->ability = new SubscriptionAbility($this);
        }

        return $this->ability;
    }

    /**
     * Find by subscribable id.
     *
     * @param Builder $query
     * @param int|string|Model $subscribable
     * @return Builder
     */
    public function scopeByUser(Builder $query, int|string|Model $subscribable): Builder
    {
        if ($subscribable instanceof Model) {
            return $query->where('subscribable_id', $subscribable->getKey());
        }
        return $query->where('subscribable_id', $subscribable);
    }

    /**
     * Find a subscription with an ending trial.
     *
     * @param Builder $query
     * @param int $dayRange
     * @return Builder
     */
    public function scopeFindEndingTrial(Builder $query, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $query->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Find a subscription with an ended trial.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFindEndedTrial(Builder $query): Builder
    {
        return $query->where('trial_ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Find ending subscriptions.
     *
     * @param Builder $query
     * @param int $dayRange
     * @return Builder
     */
    public function scopeFindEndingPeriod(Builder $query, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $query->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Scope not canceled subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeExcludeCanceled(Builder $query): Builder
    {
        return $query->whereNull('canceled_at');
    }

    /**
     * Scope not immediately canceled subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeExcludeImmediatelyCanceled(Builder $query): Builder
    {
        return $query->whereNull('canceled_immediately')
            ->orWhere('canceled_immediately', 0);
    }

    /**
     * Find ended subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFindEndedPeriod(Builder $query): Builder
    {
        return $query->where('ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Set the subscription period.
     *
     * @param string|null $interval
     * @param int|null $intervalCount
     * @param string|null $start Start date
     * @return  static
     * @throws InvalidIntervalException
     */
    public function setNewPeriod(
        ?string $interval = null,
        ?int    $intervalCount = null,
        ?string $start = null
    ): static
    {
        if (empty($interval)) {
            $interval = $this->plan->interval;
        }

        if (empty($intervalCount)) {
            $intervalCount = $this->plan->interval_count;
        }

        $period = new Period($interval, $intervalCount, $start);

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }
}
