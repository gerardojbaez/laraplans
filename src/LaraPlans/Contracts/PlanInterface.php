<?php

namespace Gerardojbaez\LaraPlans\Contracts;

interface PlanInterface
{
    public function features();
    public function subscriptions();
    public function scopeByCode($query, $code);
}