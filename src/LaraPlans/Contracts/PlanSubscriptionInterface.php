<?php

namespace Gerardojbaez\LaraPlans\Contracts;

interface PlanSubscriptionInterface
{
    public function user();
    public function plan();
    public function usage();
    public function getStatusAttribute();
    public function active();
    public function onTrial();
    public function canceled();
    public function ended();
    public function renew();
    public function cancel($immediately);
    public function changePlan($plan);
}
