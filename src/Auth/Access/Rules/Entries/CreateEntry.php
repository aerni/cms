<?php

namespace Statamic\Auth\Access\Rules\Entries;

use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Collection;

class CreateEntry extends Rule
{
    protected static $resource = Collection::class;

    public function allows(mixed $resource, Context $context): bool
    {
        /** @var Collection $resource */
        if (! $context->user) {
            return false;
        }

        return $context->user->hasPermission("create {$resource->handle()} entries");
    }
}
