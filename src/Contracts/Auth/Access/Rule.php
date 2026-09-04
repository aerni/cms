<?php

namespace Statamic\Contracts\Auth\Access;

use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Query\Builder;

interface Rule
{
    /**
     * The resource contract this rule handles.
     *
     * @return class-string
     */
    public static function resource(): string;

    /**
     * The operation this rule handles (e.g. view, edit, delete).
     */
    public static function operation(): string;

    /**
     * Determine whether this rule should apply to the given context.
     */
    public function shouldApply(Context $context): bool;

    /**
     * Determine whether the actor may perform the operation on the resource.
     * Combined with other matching rules via AND (every must allow).
     */
    public function allows(mixed $resource, Context $context): bool;

    /**
     * Apply this rule's constraints to the query.
     */
    public function apply(Builder $query, Context $context): void;
}
