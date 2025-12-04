<?php

namespace Gerardojbaez\Laraplans\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface PlanSubscriptionUsageInterface
{
    public function feature();

    public function subscription();

    public function scopeByFeatureCode(Builder $query, string $feature_code);

    public function isExpired();
}
