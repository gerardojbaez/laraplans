Eloquent Scopes
===============

.. code-block:: php

    use Gerardojbaez\Laraplans\Models\PlanSubscription;

    // Get subscriptions by plan:
    $subscriptions = PlanSubscription::byPlan($plan_id)->get();

    // Get subscription by user:
    $subscription = PlanSubscription::byUser($user_id)->first();

    // Get subscriptions with trial ending in 3 days:
    $subscriptions = PlanSubscription::findEndingTrial(3)->get();

    // Get subscriptions with ended trial:
    $subscriptions = PlanSubscription::findEndedTrial()->get();

    // Get subscriptions with period ending in 3 days:
    $subscriptions = PlanSubscription::findEndingPeriod(3)->get();

    // Get subscriptions with ended period:
    $subscriptions = PlanSubscription::findEndedPeriod()->get();