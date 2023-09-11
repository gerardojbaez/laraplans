<?php

namespace Gerardojbaez\Laraplans\Listeners\PlanSubscription;

use Gerardojbaez\Laraplans\Events\SubscriptionSaving;

class SetPeriodWhenEmpty
{
    /**
     * Handle event.
     *
     * @param SubscriptionSaving $event
     * @return void
     */
    public function handle(SubscriptionSaving $event)
    {
        // Set period if it wasn't set
        if (! $event->subscription->ends_at) {
            $event->subscription->setNewPeriod();
        }
    }
}