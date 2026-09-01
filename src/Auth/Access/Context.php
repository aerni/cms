<?php

namespace Statamic\Auth\Access;

use Statamic\Contracts\Auth\User;

class Context
{
    public function __construct(
        public readonly ?User $user,
        public readonly string $operation,
        public readonly mixed $resource,
        public readonly mixed $parent = null,
    ) {
    }
}
