<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Positive Words
    |--------------------------------------------------------------------------
    |
    | These words indicates "true" and are used to check if a particular plan
    | feature is enabled.
    |
    */
    'positive_words' => [
        'Y',
        'YES',
        'TRUE',
        'UNLIMITED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you want to use your own models you will want to update the following
    | array to make sure LaraPlans use them.
    |
    */
    'models' => [
        'plan' => 'Gerardojbaez\LaraPlans\Models\Plan',
        'plan_feature' => 'Gerardojbaez\LaraPlans\Models\PlanFeature',
        'plan_subscription' => 'Gerardojbaez\LaraPlans\Models\PlanSubscription',
        'plan_subscription_usage' => 'Gerardojbaez\LaraPlans\Models\PlanSubscriptionUsage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | The heart of this package. Here you will specify all features available
    | for your plans.
    |
    */
    'features' => [
        'SAMPLE_SIMPLE_FEATURE',
        'SAMPLE_DEFINED_FEATURE' => [
            'reseteable_interval' => 'month',
            'reseteable_count' => 2
        ],
    ],
];
