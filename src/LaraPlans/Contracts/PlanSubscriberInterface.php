<?php

namespace Gerardojbaez\LaraPlans\Contracts;

interface PlanSubscriberInterface
{
    public function getPlanAttribute();
    public function planSubscription();
    public function subscribeToPlan($plan);
}
