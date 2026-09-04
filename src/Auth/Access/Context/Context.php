<?php

namespace Statamic\Auth\Access\Context;

use Illuminate\Support\Collection;
use Statamic\Auth\Access\ContextResolver;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Query\Builder;

class Context
{
    public readonly Collection $data;

    /**
     * @param  class-string  $subject
     */
    public function __construct(
        public readonly ?User $user,
        public readonly string $operation,
        public readonly string $subject,
        Collection|array $data = [],
    ) {
        $this->data = collect($data);
    }

    public static function fromResource(
        mixed $resource,
        string $operation,
        ?User $user = null,
        array $data = [],
    ): self {
        return (new ContextResolver($user, $operation, $data))->resolveResource($resource);
    }

    public static function fromQuery(
        Builder $query,
        string $operation,
        ?User $user = null,
        array $data = [],
    ): self {
        return (new ContextResolver($user, $operation, $data))->resolveQuery($query);
    }

    public function hasHandle(string $handle): bool
    {
        return $this->hasAnyHandle($handle);
    }

    /**
     * @param  array<string>|string  $handles
     */
    public function hasAnyHandle(array|string $handles): bool
    {
        return collect($this->data->get('handles'))
            ->intersect($handles)
            ->isNotEmpty();
    }
}
