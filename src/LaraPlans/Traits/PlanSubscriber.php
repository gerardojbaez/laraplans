<?php

namespace Gerardojbaez\LaraPlans\Traits;

use App;
use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Contracts\PlanInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriptionInterface;

trait PlanSubscriber
{
    /**
     * Get user plan.
     *
     * @return \Gerardojbaez\LaraPlans\Models\Plan|null
     */
    function getPlanAttribute()
    {
        if (!$this->planSubscription)
            return null;

        return $this->planSubscription->plan;
    }

    /**
     * Get user plan subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function planSubscription()
    {
        return $this->hasOne(config('laraplans.models.plan_subscription'));
    }

    /**
     * Subscribe user to a new plan.
     *
     * @var mixed $plan Plan Id or Plan Model Instance
     * @var array $extra
     * @return \Gerardojbaez\LaraPlans\Models\PlanSubscription
     */
    function subscribeToPlan($plan)
    {
        $subscription = App::make(PlanSubscriptionInterface::class)
            ->firstOrNew(['user_id' => $this->id]);

        if (is_numeric($plan))
            $plan = App::make(PlanInterface::class)->find($plan);

        $subscription->changePlan($plan);

        // Add trial period if this is a new subscription
        if (is_null($subscription->id) AND ($trialDays = $plan->trial_period_days))
            $subscription->trial_end = (new Carbon)->addDays($trialDays);

        return $subscription;
    }
}
