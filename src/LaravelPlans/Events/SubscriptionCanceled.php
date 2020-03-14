<?php

namespace Czechbox\LaravelPlans\Events;

use Czechbox\LaravelPlans\Models\PlanSubscription;
use Illuminate\Queue\SerializesModels;

class SubscriptionCanceled
{
    use SerializesModels;

    /**
     * @var \LaravelPlans\Models\PlanSubscription
     */
    public $subscription;

    /**
     * Create a new event instance.
     *
     * @param  \LaravelPlans\Models\PlanSubscription  $subscription
     * @return void
     */
    public function __construct(PlanSubscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
