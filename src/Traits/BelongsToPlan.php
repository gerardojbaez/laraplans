<?php

namespace Gerardojbaez\Laraplans\Traits;

trait BelongsToPlan
{
    /**
     * Get plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(config('laraplans.models.plan'));
    }

    /**
     * Scope by plan id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $plan_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPlan($query, $plan_id)
    {
        return $query->where('plan_id', $plan_id);
    }
}
