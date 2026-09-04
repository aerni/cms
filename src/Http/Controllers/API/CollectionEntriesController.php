<?php

namespace Statamic\Http\Controllers\API;

use Facades\Statamic\API\FilterAuthorizer;
use Facades\Statamic\API\QueryScopeAuthorizer;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Access;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Statamic\Http\Resources\API\EntryResource;
use Statamic\Support\Str;
use Statamic\Tags\Concerns\QueriesTaxonomyTerms;

class CollectionEntriesController extends ApiController
{
    use QueriesTaxonomyTerms;

    protected $resourceConfigKey = 'collections';
    protected $routeResourceKey = 'collection';
    protected $filterPublished = true;
    protected $collectionHandle;

    public function index($collection)
    {
        $this->abortIfDisabled();

        $this->collectionHandle = $collection->handle();

        $with = $collection->entryBlueprints()
            ->flatMap(fn ($blueprint) => $blueprint->fields()->all())
            ->filter->isRelationship()->keys()->all();

        $query = $collection->queryEntries()->with($with);

        $query = $this->applyAccessRestrictions($query);

        return app(EntryResource::class)::collection(
            $this->updateAndPaginate($query)
        );
    }

    public function show($collection, $handle)
    {
        $this->abortIfDisabled();

        $entry = Entry::find($handle);

        $this->abortIfInvalid($entry, $collection);
        $this->abortIfUnpublished($entry);
        $this->abortIfInaccessible($entry);

        return app(EntryResource::class)::make($entry);
    }

    protected function getFilters()
    {
        return parent::getFilters()->reject(fn ($_, $filter) => Str::startsWith($filter, 'taxonomy:'));
    }

    protected function filter($query)
    {
        parent::filter($query);

        collect(request()->filter ?? [])
            ->filter(fn ($_, $filter) => Str::startsWith($filter, 'taxonomy:'))
            ->each(fn ($value, $filter) => $this->applyTaxonomyFilter($query, $filter, $value));

        return $this;
    }

    protected function applyTaxonomyFilter($query, $filter, $terms)
    {
        [$_, $taxonomy, $modifier] = array_pad(explode(':', $filter), 3, null);

        $values = collect(explode(',', $terms))->map(fn ($term) => "$taxonomy::$term");

        $this->queryTaxonomyTerms($query, $modifier, $values);
    }

    private function abortIfInvalid($entry, $collection)
    {
        if (! $entry || $entry->collection()->id() !== $collection->id()) {
            throw new NotFoundHttpException;
        }
    }

    private function abortIfInaccessible($entry): void
    {
        if (! $user = User::current()) {
            return;
        }

        if (! Access::for($user)->can('view')->resource($entry)) {
            throw new NotFoundHttpException;
        }
    }

    private function applyAccessRestrictions($query)
    {
        if (! $user = User::current()) {
            return $query;
        }

        return Access::for($user)->can('view')->query($query);
    }

    protected function allowedFilters()
    {
        return FilterAuthorizer::allowedForSubResources('api', 'collections', $this->collectionHandle);
    }

    protected function allowedQueryScopes()
    {
        return QueryScopeAuthorizer::allowedForSubResources('api', 'collections', $this->collectionHandle);
    }
}
