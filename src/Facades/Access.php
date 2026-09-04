<?php

namespace Statamic\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Auth\Access\AccessBuilder;
use Statamic\Auth\Access\AccessRepository;
use Statamic\Contracts\Auth\Access\Rule;

/**
 * @method static AccessRepository register(string $rule)
 * @method static AccessBuilder for(\Statamic\Contracts\Auth\User|null $user)
 * @method static \Illuminate\Support\Collection<int, Rule> all()
 * @method static Rule|null find(string $handle)
 * @method static \Illuminate\Support\Collection<int, Rule> rules(\Statamic\Auth\Access\Context\Context $context)
 *
 * @see AccessRepository
 */
class Access extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AccessRepository::class;
    }
}
