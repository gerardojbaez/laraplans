<?php

namespace Gerardojbaez\LaraPlans\Contracts;

interface PlanSubscriptionInterface
{
    public function user();
    public function plan();
    public function usage();
    public function getStatusAttribute();
    public function isActive();
    public function periodEnded();
    public function isTrialling();
    public function isCanceled();
    public function cancel($immediately);
    public function changePlan($plan);
    public function limitReached($feature_code);
    public function featureEnabled($feature_code);
    public function recordUsage($feature_code, $uses);
    public function getFeatureValue($feature_code, $default);
    public function setNewPeriod($interval, $interval_count, $start);
    public function clearUsage();
}
