<?php

namespace Gerardojbaez\Laraplans;

class SubscriptionUsageManager
{
    /**
     * Subscription model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $subscription;

    /**
     * Create new Subscription Usage Manager instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $subscription
     */
    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Record usage.
     *
     * This will create or update a usage record.
     *
     * @param string $feature
     * @param int $uses
     * @return \Gerardojbaez\Laraplans\Models\PlanSubscriptionUsage
     */
    public function record($feature, $uses = 1, $incremental = true)
    {
        $feature = new Feature($feature);

        $usage = $this->subscription->usage()->firstOrNew([
            'code' => $feature->getFeatureCode(),
        ]);

        if ($feature->isResettable()) {
        // Set expiration date when the usage record is new
            // or doesn't have one.
            if (is_null($usage->valid_until)) {
            // Set date from subscription creation date so
                // the reset period match the period specified
                // by the subscription's plan.
                $usage->valid_until = $feature->getResetDate($this->subscription->created_at);
            } // If the usage record has been expired, let's assign
            // a new expiration date and reset the uses to zero.
            elseif ($usage->isExpired() === true) {
                $usage->valid_until = $feature->getResetDate($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used = ($incremental ? $usage->used + $uses : $uses);

        $usage->save();

        return $usage;
    }

    /**
     * Reduce usage.
     *
     * @param string $feature
     * @param int $uses
     * @return \Gerardojbaez\Laraplans\Models\PlanSubscriptionUsage
     */
    public function reduce($feature, $uses = 1)
    {
        $feature = new Feature($feature);

        $usage = $this->subscription
            ->usage()
            ->byFeatureCode($feature->getFeatureCode())
            ->first();

        if (is_null($usage)) {
            return false;
        }

        $usage->used = max($usage->used - $uses, 0);

        $usage->save();

        return $usage;
    }

    /**
     * Clear usage data.
     *
     * @return $this
     */
    public function clear()
    {
        $this->subscription->usage()->delete();

        return $this;
    }
}
