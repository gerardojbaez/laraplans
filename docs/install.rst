Installation
============

Composer
--------

For the moment this version cannot be installed via ``composer require``.

Instead, I've chosen to follow the Composer VCS Install method as I'm not sure I want to take on long-term mainenance beyond my own needs. If somebody asks, or I add substantial functionality, that may change.

In your ``composer.json`` file add the following

.. code-block:: json

    {
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/czechbox/laraplans"
        }
    ],
    "require": {
        "gerardojbaez/laraplans": "dev-6.x"
        }
    }

Service Provider
----------------

Add ``Czechbox\Laraplans\LaraplansServiceProvider::class`` to your application service providers file: ``config/app.php``.

.. code-block:: php

    'providers' => [
        /**
         * Third Party Service Providers...
         */
        Czechbox\Laraplans\LaraplansServiceProvider::class,
    ]

Config File and Migrations
--------------------------

Publish package config file and migrations with the following command:

.. code-block:: bash

    php artisan vendor:publish --provider="Czechbox\Laraplans\LaraplansServiceProvider"

Then run migrations:

.. code-block:: bash

    php artisan migrate

Traits and Contracts
--------------------

Add ``Czechbox\Laraplans\Traits\PlanSubscriber`` trait and ``Czechbox\Laraplans\Contracts\PlanSubscriberInterface`` contract to your ``User`` model.

See the following example:

.. code-block:: php

    namespace App\Models;

    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Czechbox\Laraplans\Contracts\PlanSubscriberInterface;
    use Czechbox\Laraplans\Traits\PlanSubscriber;

    class User extends Authenticatable implements PlanSubscriberInterface
    {
        use PlanSubscriber;
