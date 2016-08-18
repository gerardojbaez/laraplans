<?php

namespace Gerardojbaez\LaraPlans;

use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Exceptions\InvalidIntervalException;

class Period
{
    /**
     * Map supported intervals to the appropriate
     * computation method.
     *
     * @var array
     */
    protected static $intervalMapping = [
        'day' => 'addDays',
        'week' => 'addWeeks',
        'month' => 'addMonths',
        'year' => 'addYears',
    ];

    /**
     * Starting date of the period.
     *
     * @var string
     */
    protected $start;

    /**
     * Ending date of the period.
     *
     * @var string
     */
    protected $end;

    /**
     * Interval
     *
     * @var string
     */
    protected $interval;

    /**
     * Interval count
     *
     * @var int
     */
    protected $interval_count = 1;

    /**
     * Create a new Period instance.
     *
     * @param  string $interval Interval
     * @param  int $count Interval count
     * @param  string $start Starting point
     * @throws  \Gerardojbaez\LaraPlans\Exceptions\InvalidIntervalException
     * @return  void
     */
    public function __construct($interval = 'month', $count = 1, $start = '')
    {
        if (empty($start))
            $this->start = new Carbon;
        elseif (!$start instanceOf Carbon)
            $this->start = new Carbon($start);
        else
            $this->start = $start;

        if (!$this::isValidInterval($interval))
            throw new InvalidIntervalException($interval);

        $this->interval = $interval;

        if ($count > 0)
            $this->interval_count = $count;

        $this->calculate();
    }

    /**
     * Get start date.
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->start;
    }

    /**
     * Get end date.
     *
     * @return string
     */
    public function getEndDate()
    {
        return $this->end;
    }

    /**
     * Get period interval.
     *
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Get period interval count.
     *
     * @return int
     */
    public function getIntervalCount()
    {
        return $this->interval_count;
    }

    /**
     * Check if a given interval is valid.
     *
     * @param  string $interval
     * @return boolean
     */
    public static function isValidInterval($interval)
    {
        return array_key_exists($interval, self::$intervalMapping);
    }

    /**
     * Calculate the end date of the period.
     *
     * @return void
     */
    protected function calculate()
    {
        $method = $this->getComputationMethod();
        $start = clone($this->start);
        $this->end = $start->$method($this->interval_count);
    }

    /**
     * Get computation method.
     *
     * @return string
     */
    protected function getComputationMethod()
    {
        return self::$intervalMapping[$this->interval];
    }
}