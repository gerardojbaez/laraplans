<?php

namespace Gerardojbaez\Laraplans;

use Carbon\Carbon;
use Gerardojbaez\Laraplans\Period;
use Gerardojbaez\Laraplans\Exceptions\InvalidPlanFeatureException;

class Feature
{
    /**
     * Feature code.
     *
     * @var string
     */
    protected $feature_code;

    /**
     * Feature resettable interval.
     *
     * @var string
     */
    protected $resettable_interval;

    /**
     * Feature resettable count.
     *
     * @var int
     */
    protected $resettable_count;

    /**
     * Create a new Feature instance.
     *
     * @param string $feature_code
     * @throws  \Gerardojbaez\Laraplans\Exceptions\InvalidPlanFeatureException
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
     * Get resettable interval.
     *
     * @return string|null
     */
    public function getResettableInterval()
    {
        return $this->resettable_interval;
    }

    /**
     * Get resettable count.
     *
     * @return int|null
     */
    public function getResettableCount()
    {
        return $this->resettable_count;
    }

    /**
     * Set resettable interval.
     *
     * @param string
     * @return void
     */
    public function setResettableInterval($interval)
    {
        $this->resettable_interval = $interval;
    }

    /**
     * Set resettable count.
     *
     * @param int
     * @return void
     */
    public function setResettableCount($count)
    {
        $this->resettable_count = $count;
    }

    /**
     * Check if feature is resettable.
     *
     * @return bool
     */
    public function isResettable()
    {
        return is_string($this->resettable_interval);
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

        $period = new Period($this->resettable_interval, $this->resettable_count, $dateFrom);

        return $period->getEndDate();
    }
}
