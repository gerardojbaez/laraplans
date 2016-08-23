<?php

namespace Gerarodjbaez\LaraPlans\Unit;

use Gerardojbaez\LaraPlans\Feature;
use Gerardojbaez\LaraPlans\Tests\TestCase;
use Gerardojbaez\LaraPlans\Exceptions\InvalidPlanFeatureException;

class FeatureTest extends TestCase
{
    /**
     * Can return all configured feature codes.
     *
     * @test
     * @return void
     */
    public function it_can_get_all_features()
    {
        $features = Feature::getAllFeatures();

        $this->assertEquals([
            'SAMPLE_SIMPLE_FEATURE',
            'SAMPLE_DEFINED_FEATURE'
        ], $features);
    }

    /**
     * Can check if feature code is valid.
     *
     * @test
     * @return void
     */
    public function it_can_validate_feature_code()
    {
        $this->assertTrue(Feature::isValid('SAMPLE_SIMPLE_FEATURE'));
        $this->assertTrue(Feature::isValid('SAMPLE_DEFINED_FEATURE'));
        $this->assertFalse(Feature::isValid('dummy_feature'));
    }

    /**
     * Can generate feature reset date.
     *
     * @test
     * @return void
     */
    public function it_can_generate_feature_reset_date()
    {
        $feature = new Feature('SAMPLE_DEFINED_FEATURE');
        $feature->setReseteableInterval('month');
        $feature->setReseteableCount('1');

        $this->assertEquals('2016-09-16 17:14:16', (string)$feature->getResetDate('2016-08-16 17:14:16'));
    }

    /**
     * @test
     * @return void
     */
    public function it_throw_exception_on_invalid_feature()
    {
        $this->expectException(InvalidPlanFeatureException::class);

        $feature = new Feature('dummy_feature');
    }
}
