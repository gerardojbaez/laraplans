<?php

namespace Gerardojbaez\Laraplans\Exceptions;

class InvalidIntervalException extends \Exception
{
    /**
     * Create a new InvalidPlanFeatureException instance.
     *
     * @param $feature
     * @return void
     */
    public function __construct($interval)
    {
        $this->message = "Invalid interval \"{$interval}\".";
    }
}
