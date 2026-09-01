<?php

namespace Statamic\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Auth\Access\Manager;

/**
 * @method static \Statamic\Auth\Access\Manager register(string $rule)
 * @method static \Statamic\Auth\Access\Manager forUser(\Statamic\Contracts\Auth\User|null $user)
 * @method static bool allows(string $operation, mixed $resource)
 * @method static void applyRulesTo(\Statamic\Contracts\Query\Builder $query, mixed $resource, string $operation = 'view', mixed $parent = null)
 * @method static void applyToEntry(\Statamic\Contracts\Query\Builder $query, \Statamic\Contracts\Entries\Collection|null $parent = null)
 *
 * @see Manager
 */
class Access extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Manager::class;
    }
}
