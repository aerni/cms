<?php

namespace Statamic\Auth\Access;

use Statamic\Contracts\Auth\Access\Rule as Contract;
use Statamic\Contracts\Query\Builder;
use Statamic\Extend\HasHandle;
use Statamic\Extend\RegistersItself;

abstract class Rule implements Contract
{
    use HasHandle, RegistersItself;

    public function appliesTo(Context $context): bool
    {
        return true;
    }

    public function restrictQuery(Context $context, Builder $query): Builder
    {
        return $query;
    }
}
