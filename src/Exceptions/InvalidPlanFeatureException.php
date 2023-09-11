<?php

namespace Gerardojbaez\Laraplans\Exceptions;

class InvalidPlanFeatureException extends \Exception
{
    /**
     * Create a new InvalidPlanFeatureException instance.
     *
     * @param $feature
     * @return void
     */
    public function __construct($feature)
    {
        $this->message = "Invalid plan feature: {$feature}";
    }
}
