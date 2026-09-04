<?php

namespace Statamic\Auth\Access\Rules\Entries;

use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Concerns\AuthorizesEntries;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Entry;

class EditEntry extends Rule
{
    use AuthorizesEntries;

    public function allows(mixed $resource, Context $context): bool
    {
        /** @var Entry $resource */
        return $this->canEdit($context->user, $resource);
    }
}
