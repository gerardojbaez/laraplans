<?php

namespace Gerarodjbaez\Laraplans\Unit;

use Gerardojbaez\Laraplans\Period;
use Gerardojbaez\Laraplans\Tests\TestCase;
use Gerardojbaez\Laraplans\Exceptions\InvalidIntervalException;

class PeriodTest extends TestCase
{
    /**
     * Start date for the tests.
     *
     * @var string
     */
    protected $startDate = '2016-08-18 13:55:09';

    /**
     * Can get all intervals with translations.
     *
     * @test
     * @return void
     */
    public function it_can_get_all_intervals_with_translations()
    {
        $intervals = Period::getAllIntervals();
        $this->assertEquals('Month', $intervals['month']);

        \App::setLocale('es');
        $intervals = Period::getAllIntervals();
        $this->assertEquals('Mes', $intervals['month']);
    }

    /**
     * Can calculate a daily period.
     *
     * @test
     * @return void
     */
    public function it_can_calculate_a_daily_period()
    {
        $period1 = new Period('day', 1, $this->startDate);
        $period2 = new Period('day', 2, $this->startDate);

        $expected1 = new \DateTime($this->startDate);
        $expected1->add(new \DateInterval('P1D'));

        $expected2 = new \DateTime($this->startDate);
        $expected2->add(new \DateInterval('P2D'));

        $this->assertEquals($this->startDate, (string)$period1->getStartDate());
        $this->assertEquals($expected1->format('Y-m-d H:i:s'), (string)$period1->getEndDate());

        $this->assertEquals($this->startDate, (string)$period2->getStartDate());
        $this->assertEquals($expected2->format('Y-m-d H:i:s'), (string)$period2->getEndDate());
    }

    /**
     * Can calculate a weekly period.
     *
     * @test
     * @return void
     */
    public function it_can_calculate_a_weekly_period()
    {
        $period1 = new Period('week', 1, $this->startDate);
        $period2 = new Period('week', 2, $this->startDate);

        $expected1 = new \DateTime($this->startDate);
        $expected1->add(new \DateInterval('P7D'));

        $expected2 = new \DateTime($this->startDate);
        $expected2->add(new \DateInterval('P14D'));

        $this->assertEquals($this->startDate, (string)$period1->getStartDate());
        $this->assertEquals($expected1->format('Y-m-d H:i:s'), (string)$period1->getEndDate());

        $this->assertEquals($this->startDate, (string)$period2->getStartDate());
        $this->assertEquals($expected2->format('Y-m-d H:i:s'), (string)$period2->getEndDate());
    }

    /**
     * Can calculate a monthly period.
     *
     * @test
     * @return void
     */
    public function it_can_calculate_a_monthly_period()
    {
        $period1 = new Period('month', 1, $this->startDate);
        $period2 = new Period('month', 2, $this->startDate);

        $expected1 = new \DateTime($this->startDate);
        $expected1->add(new \DateInterval('P1M'));

        $expected2 = new \DateTime($this->startDate);
        $expected2->add(new \DateInterval('P2M'));

        $this->assertEquals($this->startDate, (string)$period1->getStartDate());
        $this->assertEquals($expected1->format('Y-m-d H:i:s'), (string)$period1->getEndDate());

        $this->assertEquals($this->startDate, (string)$period2->getStartDate());
        $this->assertEquals($expected2->format('Y-m-d H:i:s'), (string)$period2->getEndDate());
    }

    /**
     * Can calculate a yearly period.
     *
     * @test
     * @return void
     */
    public function it_can_calculate_a_yearly_period()
    {
        $period1 = new Period('year', 1, $this->startDate);
        $period2 = new Period('year', 2, $this->startDate);

        $expected1 = new \DateTime($this->startDate);
        $expected1->add(new \DateInterval('P1Y'));

        $expected2 = new \DateTime($this->startDate);
        $expected2->add(new \DateInterval('P2Y'));

        $this->assertEquals($this->startDate, (string)$period1->getStartDate());
        $this->assertEquals($expected1->format('Y-m-d H:i:s'), (string)$period1->getEndDate());

        $this->assertEquals($this->startDate, (string)$period2->getStartDate());
        $this->assertEquals($expected2->format('Y-m-d H:i:s'), (string)$period2->getEndDate());
    }

    /**
     * It throws exception when a invalid feature
     * is passed.
     *
     * @test
     * @return void
     */
    public function it_throw_exception_on_invalid_interval()
    {
        $this->expectException(InvalidIntervalException::class);

        $period = new Period('dummy');
    }
}