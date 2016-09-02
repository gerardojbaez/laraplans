<?php

namespace Gerardojbaez\LaraPlans;

use Gerardojbaez\LaraPlans\Feature;

class SubscriptionAbility
{
    /**
     * Subscription model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $subscription;

    /**
     * Create a new Subscription instance.
     *
     * @return void
     */
    function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Determine if the feature is enabled and has
     * available uses.
     *
     * @param string $feature
     * @return boolean
     */
    public function canUse($feature)
    {
        // Get features and usage
        $feature_value = $this->value($feature);

        if (is_null($feature_value))
            return false;

        // Match "booleans" type value
        if ($this->enabled($feature) === true)
            return true;

        // If the feature value is zero, let's return false
        // since there's no uses available. (useful to disable
        // countable features)
        if ($feature_value === '0')
            return false;

        // Check for available uses
        return $this->remainings($feature) > 0;
    }

    /**
     * Get how many times the feature has been used.
     *
     * @param  string $feature
     * @return int
     */
    public function consumed($feature)
    {
        $usage = $this->subscription->usage->first(function ($key, $value) use ($feature) {
            return $value->code === $feature;
        });

        if (is_null($usage) OR $usage->isExpired())
            return 0;

        return $usage->used;
    }

    /**
     * Get the available uses.
     *
     * @param  string $feature
     * @return int
     */
    public function remainings($feature)
    {
        return ($this->value($feature) - $this->consumed($feature));
    }

    /**
     * Check if subscription plan feature is enabled.
     *
     * @param string $feature
     * @return bool
     */
    public function enabled($feature)
    {
        $feature_value = $this->value($feature);

        if (is_null($feature_value))
            return false;

        // If value is one of the positive words configured then the
        // feature is enabled.
        if (in_array(strtoupper($feature_value), config('laraplans.positive_words')))
            return true;

        return false;
    }

    /**
     * Get feature value.
     *
     * @param  string $feature
     * @param  mixed $default
     * @return mixed
     */
    public function value($feature, $default = null)
    {
        $feature = $this->subscription->plan->features->first(function ($key, $value) use ($feature) {
            return $value->code === $feature;
        });

        if (is_null($feature))
            return $default;

        return $feature->value;
    }
}