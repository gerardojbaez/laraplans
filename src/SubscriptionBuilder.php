<?php

namespace Gerardojbaez\Laraplans;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Gerardojbaez\Laraplans\Contracts\SubscriptionBuilderInterface;

class SubscriptionBuilder implements SubscriptionBuilderInterface
{
    /**
     * The user model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * The plan model that the user is subscribing to.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $plan;

    /**
     * The subscription name.
     *
     * @var string
     */
    protected $name;

    /**
     * Custom number of trial days to apply to the subscription.
     *
     * This will override the plan trial period.
     *
     * @var int|null
     */
    protected $trialDays;

    /**
     * Do not apply trial to the subscription.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * Create a new subscription builder instance.
     *
     * @param  mixed  $user
     * @param  string  $name  Subscription name
     * @param  mixed  $plan
     */
    public function __construct($user, $name, $plan)
    {
        $this->user = $user;
        $this->name = $name;
        $this->plan = $plan;
    }

    /**
     * Specify the trial duration period in days.
     *
     * @param  int $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Do not apply trial to the subscription.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Create a new subscription.
     *
     * @param  array  $options
     * @return \Gerardojbaez\Laraplans\Models\PlanSubscription
     */
    public function create(array $attributes = [])
    {
        $now = Carbon::now();

        if ($this->skipTrial) {
            $trialEndsAt = null;
        } elseif ($this->trialDays) {
            $trialEndsAt = ($this->trialDays ? $now->addDays($this->trialDays) : null);
        } elseif ($this->plan->hasTrial()) {
            $trialEndsAt = $now->addDays($this->plan->trial_period_days);
        } else {
            $trialEndsAt = null;
        }

        return $this->user->subscriptions()->create(array_replace([
            'plan_id' => $this->plan->id,
            'trial_ends_at' => $trialEndsAt,
            'name' => $this->name
        ], $attributes));
    }
}
