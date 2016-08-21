# LaraPlans


SaaS style recurring plans for Laravel 5.2.

> Please note: this package doesn't handle payments.

<!-- MarkdownTOC depth=3 autolink=true bracket="round" -->

- [Installation](#installation)
    - [Composer](#composer)
    - [Service Provider](#service-provider)
    - [Config file and Migrations](#config-file-and-migrations)
    - [Traits and Contracts](#traits-and-contracts)
- [Usage](#usage)
    - [Create a Plan](#create-a-plan)
    - [Subscribe User to a Plan](#subscribe-user-to-a-plan)
    - [Check plan limitations](#check-plan-limitations)
    - [Record Feature Usage](#record-feature-usage)
    - [Clear User Subscription Usage](#clear-user-subscription-usage)
    - [Check User Subscription Status](#check-user-subscription-status)
    - [Renew User Subscription](#renew-user-subscription)
    - [Cancel Subscription](#cancel-subscription)
    - [Get User Subscription](#get-user-subscription)
    - [Get User Subscription Plan](#get-user-subscription-plan)
    - [Plan Model Scopes](#plan-model-scopes)
    - [Subscription Model Scopes](#subscription-model-scopes)
- [Models](#models)
- [Config File](#config-file)

<!-- /MarkdownTOC -->


## Installation

### Composer
Add the following to your `composer.json` file:

```json
{
    "require": {
        "gerardojbaez/laraplans": "0.*"
    }
}
```

And then run in your terminal:

    composer install

### Service Provider

Add `Gerardojbaez\LaraPlans\LaraPlansServiceProvider::class` to your application service providers in `config/app.php` file:

```php
'providers' => [
    /**
     * Third Party Service Providers...
     */
    Gerardojbaez\LaraPlans\LaraPlansServiceProvider::class,
]
```

### Config file and Migrations

Publish package config file and migrations with the command:

    php artisan vendor:publish --provider="Gerardojbaez\LaraPlans\LaraPlansServiceProvider"

Then run migrations:

    php artisan migrate

### Traits and Contracts

Add `Gerardojbaez\LaraPlans\Traits\PlanSubscriber` trait and `Gerardojbaez\LaraPlans\Contracts\PlanSubscriberInterface` contract to your `User` model.

See the following example:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriberInterface;
use Gerardojbaez\LaraPlans\Traits\PlanSubscriber;

class User extends Authenticatable implements PlanSubscriberInterface
{
    use PlanSubscriber;
```

## Usage

### Create a Plan
```php
<?php

use Gerardojbaez\LaraPlans\Models\Plan;
use Gerardojbaez\LaraPlans\Models\PlanFeature;

// Pro Plan
$freePlan = Plan::create([
    'name' => 'Pro',
    'description' => 'Pro plan',
    'code' => 'pro',
    'price' => 9.99,
    'interval' => 'month',
    'interval_count' => 1,
    'trial_period_days' => 15,
    'sort_order' => 1,
]);

$freePlan->features()->saveMany([
    new PlanFeature(['code' => 'listings_per_month', 'value' => 50, 'sort_order' => 1]),
    new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10, 'sort_order' => 5]),
    new PlanFeature(['code' => 'listing_duration_days', 'value' => 30, 'sort_order' => 10]),
    new PlanFeature(['code' => 'pictures_per_listing', 'value' => 5, 'sort_order' => 15])
]);
```

### Subscribe User to a Plan

You can subscribe a user to a plan by using the `subscribeToPlan()` function available in the `PlanSubscriber` trait. This will create a new subscription if the user doesn't have one.

If both plans (current and new plan) have the same billing frequency (e.g., ` interval` and `interval_count`) the subscription will retain the same billing dates. If the plans don't have the same billing frequency, the subscription will have the new plan billing frequency, starting on the day of the change and _the subscription usage data will be cleared_.

If the new plan have a trial period and it's a new subscription, the trial period will be applied.

```php
<?php

use Auth;

$user = Auth::user();
$user->subscribeToPlan($plan_id)->save();
```

### Check plan limitations

The limit is reached when one of this conditions is _true_:

- Feature's _value is not a positive word_ (positive words are configured in the config file).
- Feature's _value is zero_.
- Feature _doesn't have remaining usages_ (i.e., when the user has used all his available uses).

```php
<?php

use Auth;

$user = Auth::user();

// Check if user has reached the limit in a particular feature in his subscription:
$user->planSubscription->limitReached('listings_per_month');

// Check if a feature is enabled
$user->planSubscription->featureEnabled('title_in_bold');

// Get feature's value
$user->planSubscription->getFeatureValue('pictures_per_listing');
```
### Record Feature Usage

```php
<?php

use Auth;

$user = Auth::user();

// Increment usage by 1
$user->planSubscription->recordUsage('listings_per_month');

// -or-

// Increment usage by custom number (perfect when user perform batch actions)
$user->planSubscription->recordUsage('listings_per_month', 3);

```
### Clear User Subscription Usage

You may want to reset all feature's usage data when user renew his subscription, in this case the `clearUsage()` function will help you:

```php
<?php

use Auth;

$user = Auth::user();
$user->planSubscription->clearUsage();
```

### Check User Subscription Status

For a subscription to be considered active one of the following must be _true_:

- Subscription `canceled_at` is `null` or in the future.
- Subscription `trial_end` is in the future.
- Subscription `current_period_end` is in the future.

```php
<?php

use Auth;

$user = Auth::user();
$user->planSubscription->isActive();

// Alternatively you can use the following:
$user->planSubscription->isCanceled();
$user->planSubscription->isTrialling();
$user->planSubscription->periodEnded();

// Get the subscription status
$user->planSubscription->status; // (active|canceled|ended)
```

### Renew User Subscription

```php
<?php

use Auth;

$user = Auth::user();
$user->planSubscription->setNewPeriod()->save();

// You may want to clear the usage data:
$user->planSubscription->setNewPeriod()->clearUsage()->save();
```

### Cancel Subscription

```php
<?php

use Auth;

$user = Auth::user();

// Cancel At Period End
$user->planSubscription->cancel();

// Cancel Immediately
$user->planSubscription->cancel(true);
```

### Get User Subscription

```php
<?php

use Auth;

$user = Auth::user();
$user->planSubscription;

// Get Subscription details
$user->planSubscription->plan;
$user->planSubscription->status; // (active|canceled|ended)
$user->planSubscription->trial_end; // null|date
$user->planSubscription->current_period_start; // date
$user->planSubscription->current_period_end; // date
$user->planSubscription->canceled_at; // null|date
$user->planSubscription->isActive(); // true|false
```

### Get User Subscription Plan

```php
<?php

use Auth;

$user = Auth::user();
$user->plan;
// -or-
$user->planSubscription->plan;

// Get plan details
$user->plan->name; // Pro
$user->plan->slug; // pro;
$user->plan->description; // Pro Features for 9.99/month.
$user->plan->price; // 9.99
$user->plan->isFree(); // true|false
$user->plan->interval; // month
$user->plan->interval_count; // 1
$user->plan->sort_order;
$user->plan->trial_period_days; // 15
```

### Plan Model Scopes

```php
<?php

use Gerardojbaez\LaraPlans\Models\Plan;

// Get subscription by plan code:
$subscription = Plan::byCode($code)->first();
```

### Subscription Model Scopes

```php
<?php

use Gerardojbaez\LaraPlans\Models\PlanSubscription;

// Get subscriptions by plan:
$subscriptions = PlanSubscription::byPlan($plan_id)->get();

// Get subscription by user:
$subscription = PlanSubscription::byUser($user_id)->first();

// Get subscriptions with trial ending in 3 days:
$subscriptions = PlanSubscription::FindEndingTrial(3)->get();

// Get subscriptions with ended trial:
$subscriptions = PlanSubscription::FindEndedTrial()->get();

// Get subscriptions with period ending in 3 days:
$subscriptions = PlanSubscription::FindEndingPeriod(3)->get();

// Get subscriptions with ended period:
$subscriptions = PlanSubscription::FindEndedPeriod()->get();
```

## Models

LaraPlans uses 4 models:

```php
Gerardojbaez\LaraPlans\Models\Plan;
Gerardojbaez\LaraPlans\Models\PlanFeature;
Gerardojbaez\LaraPlans\Models\PlanSubscription;
Gerardojbaez\LaraPlans\Models\PlanSubscriptionUsage;
```

For more details take a look to each model and the `Gerardojbaez\LaraPlans\Traits\PlanSubscriber` trait.

## Config File

You can configure what models to use, list of positive words and the list of features your app and your plans will use.

Definitions:
- **Positive Words**: Are used to tell if a particular feature is _enabled_. E.g., if the feature `listing_title_bold` has the value `Y` (_Y_ is one of the positive words) then, that means it's enabled.
- **Features**: List of features that your app and plans will use.

Take a look to the `config/laraplans.php` config file for more details.