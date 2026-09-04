<?php

namespace Statamic\Auth\Access\Rules\Entries;

use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Concerns\AuthorizesEntries;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Entry;

class PublishEntry extends Rule
{
    use AuthorizesEntries;

    public function allows(mixed $resource, Context $context): bool
    {
        /** @var Entry $resource */
        $user = $context->user;

        if (! $user) {
            return false;
        }

        if ($this->hasAnotherAuthor($user, $resource)) {
            return $user->hasPermission("publish other authors {$resource->collectionHandle()} entries");
        }

        return $user->hasPermission("publish {$resource->collectionHandle()} entries");
    }
}
