<?php

namespace Statamic\Auth\Access;

use LogicException;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Query\Builder;
use Statamic\Contracts\Query\QueryResource;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;

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
        $data = array_merge(['handles' => $this->handlesFromResource($resource)], $this->data);

        return $this->make($resource, query: null, data: $data);
    }

    public function resolveQuery(Builder $query): Context
    {
        $query = $this->ensureQuery($query);
        $resource = $this->ensureResource($query->subject());
        $data = array_merge(['handles' => $this->handlesFromQuery($query)], $this->data);

        return $this->make($resource, $query, $data);
    }

    private function make(mixed $resource, ?Builder $query, array $data): Context
    {
        return new Context($this->user, $this->operation, $resource, $query, $data);
    }

    /**
     * @return list<string>
     */
    private function handlesFromQuery(Builder&QueryResource $query): array
    {
        $subject = $query->subject();

        return match (true) {
            $this->isEntry($subject) => $this->collectionHandlesFromQuery($query),
            $this->isTerm($subject) => $this->taxonomyHandlesFromQuery($query),
            $this->isAsset($subject) => $this->containerHandlesFromQuery($query),
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
            $this->isTerm($resource) => $this->taxonomyHandlesFromResource($resource),
            $this->isAsset($resource) => $this->containerHandlesFromResource($resource),
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

    /**
     * @return list<string>
     */
    private function taxonomyHandlesFromResource(Term $resource): array
    {
        return [$resource->taxonomyHandle()];
    }

    /**
     * @return list<string>
     */
    private function taxonomyHandlesFromQuery(Builder $query): array
    {
        $handles = $query->taxonomies();

        return empty($handles)
            ? Taxonomy::handles()->all()
            : array_values($handles);
    }

    /**
     * @return list<string>
     */
    private function containerHandlesFromResource(Asset $resource): array
    {
        return [$resource->containerHandle()];
    }

    /**
     * @return list<string>
     */
    private function containerHandlesFromQuery(Builder $query): array
    {
        return [$query->getContainer()->handle()];
    }

    private function isEntry(mixed $resource): bool
    {
        return $this->is($resource, CollectionContract::class) || $this->is($resource, Entry::class);
    }

    private function isTerm(mixed $resource): bool
    {
        return $this->is($resource, Term::class);
    }

    private function isAsset(mixed $resource): bool
    {
        return $this->is($resource, Asset::class);
    }

    private function is(mixed $resource, string $type): bool
    {
        return is_a(is_object($resource) ? $resource::class : $resource, $type, true);
    }
}
