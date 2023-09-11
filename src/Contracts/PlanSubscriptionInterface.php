<?php

namespace Gerardojbaez\Laraplans\Contracts;

interface PlanSubscriptionInterface
{
    public function subscribable();
    public function plan();
    public function usage();
    public function getStatusAttribute();
    public function isActive();
    public function onTrial();
    public function isCanceled();
    public function isEnded();
    public function renew();
    public function cancel($immediately);
    public function changePlan($plan);
}
