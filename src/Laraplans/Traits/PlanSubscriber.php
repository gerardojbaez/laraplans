<?php

namespace Gerardojbaez\Laraplans\Traits;

use App;
use Carbon\Carbon;
use Gerardojbaez\Laraplans\SubscriptionBuilder;
use Gerardojbaez\Laraplans\SubscriptionUsageManager;
use Gerardojbaez\Laraplans\Contracts\PlanInterface;
use Gerardojbaez\Laraplans\Contracts\PlanSubscriptionInterface;

trait PlanSubscriber
{
    /**
     * Get a subscription by name.
     *
     * @param  string $name
     * @return \Gerardojbaez\Laraplans\Models\PlanSubscription|null
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
        return $this->morphMany(config('laraplans.models.plan_subscription'), 'subscribable');
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
            return $subscription->isActive();
        }

        if ($planId == $subscription->plan_id and $subscription->isActive()) {
            return true;
        }

        return false;
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param string $subscription
     * @param mixed $plan
     * @return \Gerardojbaez\Laraplans\Models\PlanSubscription
     */
    public function newSubscription($subscription, $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

    /**
     * Get subscription usage manager instance.
     *
     * @param  string $subscription
     * @return \Gerardojbaez\Laraplans\SubscriptionUsageManager
     */
    public function subscriptionUsage($subscription = 'default')
    {
        return new SubscriptionUsageManager($this->subscription($subscription));
    }
}
