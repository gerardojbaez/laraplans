<?php

namespace Gerardojbaez\LaraPlans;

use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Period;
use Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException;

class Feature
{
    /**
     * Feature code.
     *
     * @var string
     */
    protected $feature_code;

    /**
     * Feature reseteable interval.
     *
     * @var string
     */
    protected $reseteable_interval;

    /**
     * Feature reseteable count.
     *
     * @var int
     */
    protected $reseteable_count;

    /**
     * Create a new Feature instance.
     *
     * @param string $feature_code
     * @throws  \Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException
     * @return void
     */
    public function __construct($feature_code)
    {
        if (!self::isValid($feature_code)) {
            throw new InvalidPlanFeatureException($feature_code);
        }

        $this->feature_code = $feature_code;

        $feature = config('laraplans.features.'.$feature_code);

        if (is_array($feature)) {
            foreach ($feature as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Get all features listed in config file.
     *
     * @return array
     */
    public static function getAllFeatures()
    {
        $features = config('laraplans.features');

        if (!$features) {
            return [];
        }

        $codes = [];

        foreach ($features as $key => $value) {
            if (is_string($value)) {
                $codes[] = $value;
            } else {
                $codes[] = $key;
            }
        }

        return $codes;
    }

    /**
     * Check if feature code is valid.
     *
     * @param string $code
     * @return bool
     */
    public static function isValid($code)
    {
        $features = config('laraplans.features');

        if (array_key_exists($code, $features)) {
            return true;
        }

        if (in_array($code, $features)) {
            return true;
        }

        return false;
    }

    /**
     * Get feature code.
     *
     * @return string
     */
    public function getFeatureCode()
    {
        return $this->feature_code;
    }

    /**
     * Get reseteable interval.
     *
     * @return string|null
     */
    public function getReseteableInterval()
    {
        return $this->reseteable_interval;
    }

    /**
     * Get reseteable count.
     *
     * @return int|null
     */
    public function getResetableCount()
    {
        return $this->reseteable_count;
    }

    /**
     * Set reseteable interval.
     *
     * @param string
     * @return void
     */
    public function setReseteableInterval($interval)
    {
        $this->reseteable_interval = $interval;
    }

    /**
     * Set reseteable count.
     *
     * @param int
     * @return void
     */
    public function setReseteableCount($count)
    {
        $this->reseteable_count = $count;
    }

    /**
     * Check if feature is reseteable.
     *
     * @return bool
     */
    public function isReseteable()
    {
        return is_string($this->reseteable_interval);
    }

    /**
     * Get feature's reset date.
     *
     * @param string $dateFrom
     * @return \Carbon\Carbon
     */
    public function getResetDate($dateFrom = '')
    {
        if (empty($dateFrom)) {
            $dateFrom = new Carbon;
        }

        $period = new Period($this->reseteable_interval, $this->reseteable_count, $dateFrom);

        return $period->getEndDate();
    }
}
