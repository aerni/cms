<?php

namespace Statamic\Contracts\Auth\Access;

use Statamic\Auth\Access\Context;
use Statamic\Contracts\Query\Builder as QueryBuilder;

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
    public function operation(): string;

    /**
     * Determine whether this rule applies to the given context.
     */
    public function appliesTo(Context $context): bool;

    /**
     * Determine whether the actor may perform the operation.
     *
     * When a matching rule is registered, this is the complete decision for that context.
     */
    public function allows(Context $context): bool;

    public function restrictQuery(Context $context, QueryBuilder $query): QueryBuilder;
}
