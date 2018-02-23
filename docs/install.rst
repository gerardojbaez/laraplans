Installation
============

Composer
--------

.. code-block:: bash

    $ composer require gerardojbaez/laraplans

Service Provider
----------------

Add ``Gerardojbaez\Laraplans\LaraplansServiceProvider::class`` to your application service providers file: ``config/app.php``.

.. code-block:: php

    'providers' => [
        /**
         * Third Party Service Providers...
         */
        Gerardojbaez\Laraplans\LaraplansServiceProvider::class,
    ]

Config File and Migrations
--------------------------

Publish package config file and migrations with the following command:

.. code-block:: bash

    php artisan vendor:publish --provider="Gerardojbaez\Laraplans\LaraplansServiceProvider"

Then run migrations:

.. code-block:: bash

    php artisan migrate

Traits and Contracts
--------------------

Add ``Gerardojbaez\Laraplans\Traits\PlanSubscriber`` trait and ``Gerardojbaez\Laraplans\Contracts\PlanSubscriberInterface`` contract to your ``User`` model.

See the following example:

.. code-block:: php

    <?php

    namespace App\Models;

    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Gerardojbaez\Laraplans\Contracts\PlanSubscriberInterface;
    use Gerardojbaez\Laraplans\Traits\PlanSubscriber;

    class User extends Authenticatable implements PlanSubscriberInterface
    {
        use PlanSubscriber;
