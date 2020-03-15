<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreatePlansTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // plans table
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 7, 2)->default('0.00');
            $table->string('interval')->default('month');
            $table->smallInteger('interval_count')->default(1);
            $table->smallInteger('trial_period_days')->nullable();
            $table->smallInteger('sort_order')->nullable();
            $table->timestamps();
        });

        // plan features table
        Schema::create('plan_features', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('plan_id')->unsigned();
            $table->string('code');
            $table->string('value');
            $table->smallInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'code']);
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        // plan subscriptions table
        Schema::create('plan_subscriptions', function (Blueprint $table) {
            $table->increments('id');

            $table->morphs('subscribable'); //this accomplishes what the following commented out lines did previously.
//            If your User model id is a 'uuid' rather than 'increments', change to '$table->uuidMorphs('suscribable');'
//
//            $table->integer('subscribable_id')->unsigned()->index();
//            $table->string('subscribable_type')->index();

            $table->integer('plan_id')->unsigned();
            $table->string('name');
            $table->boolean('canceled_immediately')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        // plan subscriptions usage table
        Schema::create('plan_subscription_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('subscription_id')->unsigned();
            $table->string('code');
            $table->smallInteger('used')->unsigned();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'code']);
            $table->foreign('subscription_id')->references('id')->on('plan_subscriptions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_subscription_usages');
        Schema::dropIfExists('plan_subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
}