<?php

namespace Statamic\Auth\Access\Rules;

use LogicException;
use Illuminate\Support\Collection as SupportCollection;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Auth\Access\Rule as Contract;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Extend\HasHandle;
use Statamic\Extend\RegistersItself;

abstract class Rule implements Contract
{
    use HasHandle, RegistersItself;

    /** @var class-string|null */
    protected static $resource;

    /** @var string|null */
    protected static $operation;

    public static function resource(): string
    {
        if (static::$resource) {
            return static::$resource;
        }

        $type = static::resourceFromClassName();

        return static::resources()[$type]
            ?? throw new LogicException("Unable to determine resource type [{$type}] from [".static::class.']. Set protected static $resource.');
    }

    public static function operation(): string
    {
        return static::$operation ?? static::operationFromClassName();
    }

    abstract public function allows(Context $context): bool;

    public function shouldApply(Context $context): bool
    {
        return true;
    }

    public function apply(Context $context): void
    {
        //
    }

    /**
     * @return array<string, class-string>
     */
    protected static function resources(): array
    {
        return [
            'Entry' => Entry::class,
            'Collection' => Collection::class,
        ];
    }

    private static function operationFromClassName(): string
    {
        return static::classNameSegments()->first();
    }

    private static function resourceFromClassName(): string
    {
        $resource = static::classNameSegments()->skip(1)->implode('_');

        if (! $resource) {
            throw new LogicException('Unable to determine resource from ['.class_basename(static::class).']. Set protected static $resource.');
        }

        return str($resource)->studly()->toString();
    }

    private static function classNameSegments(): SupportCollection
    {
        return str(class_basename(static::class))->snake()->explode('_');
    }
}
