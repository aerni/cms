<?php

namespace Statamic\Auth\Access;

use LogicException;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Query\Builder;
use Statamic\Contracts\Query\QueryResource;

class ContextResolver
{
    public function __construct(
        private readonly ?User $user,
        private readonly string $operation,
        private readonly array $data = [],
    ) {
    }

    public function resolveResource(mixed $resource): Context
    {
        $resource = $this->ensureResource($resource);
        $subject = is_object($resource) ? $resource::class : $resource;

        return new Context($this->user, $this->operation, $subject, $this->data);
    }

    public function resolveQuery(Builder $query): Context
    {
        $query = $this->ensureQuery($query);
        $subject = $this->ensureResource($query->subject());
        $subject = is_object($subject) ? $subject::class : $subject;

        return new Context($this->user, $this->operation, $subject, $this->data);
    }

    private function ensureQuery(Builder $query): Builder&QueryResource
    {
        if (! $query instanceof QueryResource) {
            throw new LogicException('The query must implement ['.QueryResource::class.'] to apply access rules.');
        }

        return $query;
    }

    private function ensureResource(mixed $resource): mixed
    {
        if (is_object($resource)) {
            return $resource;
        }

        if (is_string($resource) && (class_exists($resource) || interface_exists($resource))) {
            return $resource;
        }

        throw new LogicException('A resource instance or class string is required to evaluate access.');
    }
}
