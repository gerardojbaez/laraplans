Usage
=====

Create a Plan
-------------

.. code-block:: php

    <?php

    use Gerardojbaez\Laraplans\Models\Plan;
    use Gerardojbaez\Laraplans\Models\PlanFeature;

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

Accessing Plan Features
-----------------------

In some cases you need to access a particular feature in a particular plan, you can accomplish this by using the ``getFeatureByCode`` method available in the ``Plan`` model.

Example:

.. code-block:: php

    $feature = $plan->getFeatureByCode('pictures_per_listing');
    $feature->value // Get the feature's value

Create a Subscription
---------------------

First, retrieve an instance of your subscriber model, which typically will be your user model and an instance of the plan your user is subscribing to. Once you have retrieved the model instance, you may use the ``newSubscription`` method (available in ``PlanSubscriber`` trait) to create the model's subscription.

.. code-block:: php

    <?php

    use Auth;
    use Gerardojbaez\Laraplans\Models\Plan;

    $user = Auth::user();
    $plan = Plan::find(1);

    $user->newSubscription('main', $plan)->create();

The first argument passed to ``newSubscription`` method should be the name of the subscription. If your application offer a single subscription, you might call this ``main`` or ``primary``. Subscription's name is not the Plan's name, it is an *unique* subscription identifier. The second argument is the plan instance your user is subscribing to.

Subscription's Ability
----------------------

There are multiple ways to determine the usage and ability of a particular feature in the user's subscription, the most common one is ``canUse``:

The ``canUse`` method returns ``true`` or ``false`` depending on multiple factors:

- Feature *is enabled*
- Feature value isn't ``0``.
- Or feature has remaining uses available

.. code-block:: php

    $user->subscription('main')->ability()->canUse('listings');

**There are other ways to determine the ability of a subscription:**

- ``enabled``: returns ``true`` when the value of the feature is a *positive word* listed in the config file.
- ``consumed``: returns how many times the user has used a particular feature.
- ``remainings``: returns available uses for a particular feature.
- ``value``: returns the feature value.

All methods share the same signature: ``$user->subscription('main')->ability()->consumed('listings');``.

Record Feature Usage
--------------------

In order to efectively use the ability methods you will need to keep track of every usage of usage based features. You may use the ``record`` method available through the user ``subscriptionUsage()`` method:

.. code-block::php

    $user->subscriptionUsage('main')->record('listings');

The ``record`` method accepts 3 parameters: the first one is the feature's code, the second one is the quantity of uses to add (default is ``1``), and the third one indicates if the usage should be incremented (``true``: default behavior) or overriden (``false``).

See the following example:

.. code-block:: php

    // Increment by 2
    $user->subscriptionUsage('main')->record('listings', 2);

    // Override with 9
    $user->subscriptionUsage('main')->record('listings', 9, false);

Reduce Feature Usage
--------------------

Reducing the feature usage is *almost* the same as incrementing it. In this case we only *substract* a given quantity (default is ``1``) to the actual usage:

.. code-block:: php

    // Reduce by 1
    $user->subscriptionUsage('main')->reduce('listings');

    // Reduce by 2
    $user->subscriptionUsage('main')->reduce('listings', 2);


Clear The Subscription Usage Data
---------------------------------

In some cases you will need to clear all usages in a particular user subscription, you can accomplish this by using the ``clear`` method:

.. code-block:: php

    $user->subscriptionUsage('main')->clear();

Check Subscription Status
-------------------------

For a subscription to be considered **active** the subscription must have an active trial or subscription's ``ends_at`` is in the future.

.. code-block:: php

    $user->subscribed('main');
    $user->subscribed('main', $planId); // Check if subscription is active AND using a particular plan

Alternatively, you can use the following methods available in the subscription model:

.. code-block:: php

    $user->subscription('main')->isActive();
    $user->subscription('main')->isCanceled();
    $user->subscription('main')->isCanceledImmediately();
    $user->subscription('main')->isEnded();
    $user->subscription('main')->isOnTrial();

.. caution::
    **Canceled** subscriptions **with** an active trial or ``ends_at`` in the future are considered active.

Renew a Subscription
--------------------

To renew a subscription you may use the ``renew`` method available in the subscription model. This will set a new ``ends_at`` date based on the selected plan and **will clear the usage data** of the subscription.

.. code-block:: php

    $user->subscription('main')->renew();

.. caution::
    Canceled subscriptions with an ended period can't be renewed.

``Gerardojbaez\Laraplans\Events\SubscriptionRenewed`` event is fired when a subscription is renewed using the ``renew`` method.

Cancel a Subscription
---------------------

To cancel a subscription, simply use the ``cancel``  method on the user's subscription:

.. code-block:: php

    $user->subscription('main')->cancel();


By default, the subscription will remain active until the perdiod ends. Pass ``true`` to *immediately* cancel a subscription.

.. code-block:: php

    $user->subscription('main')->cancel(true);

