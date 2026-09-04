<?php

namespace Statamic\Contracts\Auth\Access;

use Statamic\Auth\Access\Context\Context;

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
     * Determine whether the actor may perform the operation.
     * When a matching rule is registered, this is the complete decision for that context.
     */
    public function allows(Context $context): bool;

    /**
     * Apply this rule's constraints to the query on the context.
     */
    public function apply(Context $context): void;
}
