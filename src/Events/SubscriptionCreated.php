<?php

namespace Gerardojbaez\Laraplans\Events;

use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
{
    use SerializesModels;

    /**
     * @var \Laraplans\Models\PlanSubscription
     */
    public $subscription;

    /**
     * Create a new event instance.
     *
     * @param  \Laraplans\Models\PlanSubscription  $subscription
     * @return void
     */
    public function __construct(PlanSubscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
