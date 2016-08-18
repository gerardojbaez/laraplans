<?php

namespace Gerardojbaez\LaraPlans\Exceptions;

class InvalidPlanFeatureException extends \Exception
{
    /**
     * Create a new InvalidPlanFeatureException instance.
     *
     * @param $feature
     * @return void
     */
    function __construct($feature)
    {
        $this->message = "Invalid plan feature: {$feature}";
    }
}
