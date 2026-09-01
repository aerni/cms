<?php

namespace Statamic\Auth\Access;

use Statamic\Contracts\Auth\Access\Rule as Contract;
use Statamic\Contracts\Query\Builder;
use Statamic\Extend\HasHandle;
use Statamic\Extend\RegistersItself;

abstract class Rule implements Contract
{
    use HasHandle, RegistersItself;

    /** @var class-string */
    protected static $resource;

    /** @var string */
    protected static $operation;

    public static function resource(): string
    {
        return static::$resource;
    }

    public static function operation(): string
    {
        return static::$operation;
    }

    public function appliesTo(Context $context): bool
    {
        return true;
    }

    public function apply(Builder $query, Context $context): void
    {
    }
}
