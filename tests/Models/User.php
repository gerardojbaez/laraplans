<?php

namespace Gerardojbaez\Laraplans\Tests\Models;

use Gerardojbaez\Laraplans\Contracts\PlanSubscriberInterface;
use Gerardojbaez\Laraplans\Database\Factories\UserFactory;
use Gerardojbaez\Laraplans\Traits\PlanSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements PlanSubscriberInterface
{
    use HasFactory;
    use PlanSubscriber;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static function newFactory()
    {
        return new UserFactory();
    }
}
