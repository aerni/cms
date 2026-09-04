<?php

namespace Tests\Auth\Access\Fakes;

use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Query\Builder;

class FakeApplyToEntryQueryRule extends Rule
{
    protected static $resource = EntryContract::class;
    protected static $operation = 'view';

    public function allows(mixed $resource, Context $context): bool
    {
        return $resource->slug() !== 'secret';
    }

    public function apply(Builder $query, Context $context): void
    {
        $query->where('slug', '!=', 'secret');
    }
}
