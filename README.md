# LaraPlans


SaaS style recurring plans for Laravel 5.

> Please note: this package doesn't handle payments.

<!-- MarkdownTOC depth="2" autolink="true" bracket="round" -->

- [Considerations](#considerations)
- [Installation](#installation)
    - [Composer](#composer)
    - [Service Provider](#service-provider)
    - [Config file and Migrations](#config-file-and-migrations)
    - [Traits and Contracts](#traits-and-contracts)
- [Usage](#usage)
    - [Create a Plan](#create-a-plan)
    - [Creating subscriptions](#creating-subscriptions)
    - [Subscription Ability](#subscription-ability)
    - [Record Feature Usage](#record-feature-usage)
    - [Reduce Feature Usage](#reduce-feature-usage)
    - [Clear The Subscription Usage Data](#clear-the-subscription-usage-data)
    - [Check Subscription Status](#check-subscription-status)
    - [Renew a Subscription](#renew-a-subscription)
    - [Cancel a Subscription](#cancel-a-subscription)
    - [Scopes](#scopes)
- [Models](#models)
- [Config File](#config-file)

<!-- /MarkdownTOC -->

## Considerations

- Payments are out of scope for this package.
- You may want to extend all of LaraPlans models since it's likely that you will need to override the logic behind some helper methods like `renew()`, `cancel()` etc. E.g.: when cancelling a subscription you may want to also cancel the recurring payment attached.

## Installation

### Composer
Add the following to your `composer.json` file:

```json
{
    "require": {
        "gerardojbaez/laraplans": "~1.0"
    }
}
```

And then run in your terminal:

    composer install

#### Quick Installation

Above installation can also be simplify by using the following command:

    composer require "gerardojbaez/laraplans=~1.0"

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

$plan = Plan::create([
    'name' => 'Pro',
    'description' => 'Pro plan',
    'price' => 9.99,
    'interval' => 'month',
    'interval_count' => 1,
    'trial_period_days' => 15,
    'sort_order' => 1,
]);

$plan->features()->saveMany([
    new PlanFeature(['code' => 'listings', 'value' => 50, 'sort_order' => 1]),
    new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10, 'sort_order' => 5]),
    new PlanFeature(['code' => 'listing_duration_days', 'value' => 30, 'sort_order' => 10]),
    new PlanFeature(['code' => 'listing_title_bold', 'value' => 'Y', 'sort_order' => 15])
]);
```

### Get the value of Feature 

Say you want to show the value of the feature _pictures_per_listing_ from above. You can use `getFeatureByCode()`.

```php
$amountOfPictures = $plan->getFeatureByCode('pictures_per_listing')->value
```

### Creating subscriptions

You can subscribe a user to a plan by using the `newSubscription()` function available in the `PlanSubscriber` trait. First, retrieve an instance of your subscriber model, which typically will be your user model and an instance of the plan your user is subscribing to. Once you have retrieved the model instance, you may use the `newSubscription` method to create the model's subscription.

```php
<?php

use Auth;
use Gerardojbaez\LaraPlans\Models\Plan;

$user = Auth::user();
$plan = Plan::find(1);

$user->newSubscription('main', $plan)->create();
```

The first argument passed to `newSubscription` method should be the name of the subscription. If your application offer a single subscription, you might call this `main` or `primary`. The second argument is the plan instance your user is subscribing to.

<!-- ~~If both plans (current and new plan) have the same billing frequency (e.g., ` interval` and `interval_count`) the subscription will retain the same billing dates. If the plans don't have the same billing frequency, the subscription will have the new plan billing frequency, starting on the day of the change and _the subscription usage data will be cleared_.~~ -->

<!-- ~~If the new plan have a trial period and it's a new subscription, the trial period will be applied.~~ -->

### Subscription Ability

There's multiple ways to determine the usage and ability of a particular feature in the user subscription, the most common one is `canUse`:

The `canUse` method returns `true` or `false` depending on multiple factors:

- Feature _is enabled_.
- Feature value isn't `0`.
- Or feature has remaining uses available.

```php
$user->subscription('main')->ability()->canUse('listings');
```

Other methods are:

- `enabled`: returns `true` when the value of the feature is a _positive word_ listed in the config file.
- `consumed`: returns how many times the user has used a particular feature.
- `remainings`: returns available uses for a particular feature.
- `value`: returns the feature value.

> All methods share the same signature: e.g. `$user->subscription('main')->ability()->consumed('listings');`.


### Record Feature Usage

In order to efectively use the ability methods you will need to keep track of every usage of each feature (or at least those that require it). You may use the `record` method available through the user `subscriptionUsage()` method:

```php
$user->subscriptionUsage('main')->record('listings');
```
The `record` method accept 3 parameters: the first one is the feature's code, the second one is the quantity of uses to add (default is `1`), and the third one indicates if the addition should be incremental (default behavior), when disabled the usage will be override by the quantity provided.

E.g.:

```php
// Increment by 2
$user->subscriptionUsage('main')->record('listings', 2);

// Override with 9
$user->subscriptionUsage('main')->record('listings', 9, false);
```

### Reduce Feature Usage

Reducing the feature usage is _almost_ the same as incrementing it. Here we only _substract_ a given quantity (default is `1`) to the actual usage:

```php
$user->subscriptionUsage('main')->reduce('listings', 2);
```

### Clear The Subscription Usage Data

```php
$user->subscriptionUsage('main')->clear();
```

### Check Subscription Status

For a subscription to be considered active _one of the following must be `true`_:

- Subscription has an active trial.
- Subscription `ends_at` is in the future.

```php
$user->subscribed('main');
$user->subscribed('main', $planId); // Check if user is using a particular plan
```

Alternatively you can use the following methods available in the subscription model:

```php
$user->subscription('main')->active();
$user->subscription('main')->canceled();
$user->subscription('main')->ended();
$user->subscription('main')->onTrial();
```

> Canceled subscriptions with an active trial or `ends_at` in the future are considered active.

### Renew a Subscription

To renew a subscription you may use the `renew` method available in the subscription model. This will set a new `ends_at` date based on the selected plan and _will clear the usage data_ of the subscription.

```php
$user->subscription('main')->renew();
```

_Canceled subscriptions with an ended period can't be renewed._

### Cancel a Subscription

To cancel a subscription, simply use the `cancel` method on the user's subscription:

```php
$user->subscription('main')->cancel();
```

By default the subscription will remain active until the end of the period, you may pass `true` to end the subscription _immediately_:

```php
$user->subscription('main')->cancel(true);
```

### Scopes

#### Subscription Model
```php
<?php

use Gerardojbaez\LaraPlans\Models\PlanSubscription;

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
