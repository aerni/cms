<?php

namespace Statamic\Contracts\Auth\Access;

use Statamic\Auth\Access\Context;
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
     *
     * @return string
     */
    public static function operation(): string;

    /**
     * Determine whether this rule applies to the given context.
     *
     * @param  \Statamic\Auth\Access\Context  $context
     * @return bool
     */
    public function appliesTo(Context $context): bool;

    /**
     * Determine whether the actor may perform the operation.
     * When a matching rule is registered, this is the complete decision for that context.
     *
     * @param  \Statamic\Auth\Access\Context  $context
     * @return bool
     */
    public function allows(Context $context): bool;

    /**
     * Apply this rule's constraints to the given query builder.
     *
     * @param  \Statamic\Contracts\Query\Builder  $query
     * @param  \Statamic\Auth\Access\Context  $context
     * @return void
     */
    public function apply(Builder $query, Context $context): void;
}
