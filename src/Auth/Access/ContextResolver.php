<?php

namespace Statamic\Auth\Access;

use LogicException;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Query\Builder;
use Statamic\Contracts\Query\QueryResource;
use Statamic\Facades\Collection;

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
        $data = array_merge(['handles' => $this->handlesFromResource($resource)], $this->data);

        return new Context($this->user, $this->operation, $subject, $data);
    }

    public function resolveQuery(Builder $query): Context
    {
        $query = $this->ensureQuery($query);
        $subject = $this->ensureResource($query->subject());
        $subject = is_object($subject) ? $subject::class : $subject;
        $data = array_merge(['handles' => $this->handlesFromQuery($query)], $this->data);

        return new Context($this->user, $this->operation, $subject, $data);
    }

    /**
     * @return list<string>
     */
    private function handlesFromQuery(Builder&QueryResource $query): array
    {
        $subject = $query->subject();

        return match (true) {
            $this->isEntry($subject) => $this->collectionHandlesFromQuery($query),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function handlesFromResource(mixed $resource): array
    {
        return match (true) {
            $this->isEntry($resource) => $this->collectionHandlesFromResource($resource),
            default => [],
        };
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

    /**
     * @return list<string>
     */
    private function collectionHandlesFromResource(Entry|CollectionContract $resource): array
    {
        return $this->is($resource, Entry::class)
            ? [$resource->collectionHandle()]
            : [$resource->handle()];
    }

    /**
     * @return list<string>
     */
    private function collectionHandlesFromQuery(Builder $query): array
    {
        $handles = $query->collections();

        return empty($handles)
            ? Collection::handles()->all()
            : array_values($handles);
    }

    private function isEntry(mixed $resource): bool
    {
        return $this->is($resource, CollectionContract::class) || $this->is($resource, Entry::class);
    }

    private function is(mixed $resource, string $type): bool
    {
        return is_a(is_object($resource) ? $resource::class : $resource, $type, true);
    }
}
