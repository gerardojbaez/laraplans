<?php

namespace Gerardojbaez\LaraPlans\Traits;

use App;
use Carbon\Carbon;
use Gerardojbaez\LaraPlans\SubscriptionBuilder;
use Gerardojbaez\LaraPlans\SubscriptionUsageManager;
use Gerardojbaez\LaraPlans\Contracts\PlanInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriptionInterface;

trait PlanSubscriber
{
    /**
     * Get a subscription by name.
     *
     * @param  string $name
     * @return \Gerardojbaez\LaraPlans\Models\PlanSubscription|null
     */
    public function subscription($name = 'default')
    {
        $subscriptions = $this->subscriptions->sortByDesc(function ($value) {
            return $value->created_at->getTimestamp();
        });

        foreach ($subscriptions as $key => $subscription) {
            if ($subscription->name === $name) {
                return $subscription;
            }
        }
    }

    /**
     * Get user plan subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscriptions()
    {
        return $this->hasMany(config('laraplans.models.plan_subscription'));
    }

    /**
     * Check if the user has a given subscription.
     *
     * @param  string $subscription
     * @param  int $planId
     * @return bool
     */
    public function subscribed($subscription = 'default', $planId = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription)) {
            return false;
        }

        if (is_null($planId)) {
            return $subscription->active();
        }

        if ($planId == $subscription->plan_id and $subscription->active()) {
            return true;
        }

        return false;
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param string $subscription
     * @param mixed $plan
     * @return \Gerardojbaez\LaraPlans\Models\PlanSubscription
     */
    public function newSubscription($subscription, $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

    /**
     * Get subscription usage manager instance.
     *
     * @param  string $subscription
     * @return \Gerardojbaez\LaraPlans\SubscriptionUsageManager
     */
    public function subscriptionUsage($subscription = 'default')
    {
        return new SubscriptionUsageManager($this->subscription($subscription));
    }
}
