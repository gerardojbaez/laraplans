<?php

namespace Gerardojbaez\Laraplans\Exceptions;

class FeatureValueFormatIncompatibleException extends \Exception
{
    /**
     * Create a new FeatureValueFormatIncompatibleException instance.
     *
     * @param $feature
     * @return void
     */
    public function __construct($value)
    {
        $this->message = "Feature value format is incompatible: {$value}.";
    }
}
