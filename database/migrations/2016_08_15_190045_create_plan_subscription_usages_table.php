<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePlanSubscriptionUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_subscription_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('subscription_id')->unsigned();
            $table->string('code');
            $table->smallInteger('used')->unsigned();
            $table->dateTime('valid_until')->nullable();
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
        Schema::drop('plan_subscription_usages');
    }
}
