<?php

namespace Statamic\Auth\Access\Rules\Entries;

use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Concerns\AuthorizesEntries;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Entry;

class ViewEntry extends Rule
{
    use AuthorizesEntries;

    public function allows(mixed $resource, Context $context): bool
    {
        /** @var Entry $resource */
        $user = $context->user;

        if (! $user) {
            return false;
        }

        if ($this->canEdit($user, $resource)) {
            return true;
        }

        if (! $this->userCanAccessSite($user, $resource->site())) {
            return false;
        }

        return $user->hasPermission("view {$resource->collectionHandle()} entries");
    }
}
