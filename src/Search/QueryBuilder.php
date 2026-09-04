<?php

namespace Statamic\Search;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Query\QueryResource;
use Statamic\Contracts\Search\Result;
use Statamic\Data\DataCollection;
use Statamic\Query\Concerns\FakesQueries;
use Statamic\Query\IteratorBuilder as BaseQueryBuilder;
use Statamic\Search\Searchables\Providers;
use Statamic\Support\Str;

abstract class QueryBuilder extends BaseQueryBuilder implements QueryResource
{
    use FakesQueries;

    protected $query;
    protected $index;
    protected $withData = true;
    protected array $collections = [];

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function subject(): string
    {
        return Entry::class;
    }

    public function collections(): array
    {
        return $this->collections;
    }

    public function query($query)
    {
        $this->query = $query;

        return $this;
    }

    public function withData(bool $with)
    {
        $this->withData = $with;

        return $this;
    }

    public function withoutData()
    {
        $this->withData = false;

        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'collection') {
            $this->collections[] = is_null($value) ? $operator : $value;

            return parent::where($column, $operator, $value, $boolean);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if (in_array($column, ['collection', 'collections'])) {
            $this->collections = array_merge($this->collections, $values);
        }

        return parent::whereIn($column, $values, $boolean);
    }

    public function get($columns = ['*'])
    {
        return $this->withFakeQueryLogging(fn () => parent::get($columns));
    }

    public function getBaseItems()
    {
        $results = $this->getSearchResults($this->query);

        return $this->transformResults($results);
    }

    public function transformResults($results)
    {
        if (! $this->withData) {
            return $this->collect($results)
                ->map(fn ($result) => new PlainResult($result))
                ->each(fn (Result $result, $i) => $result->setIndex($this->index)->setScore($results[$i]['search_score'] ?? null));
        }

        return $this->collect($results)->groupBy(function ($result) {
            return Str::before($result['reference'], '::');
        })->flatMap(function ($results, $prefix) {
            $results = $results->keyBy('reference');
            $ids = $results->map(fn ($result) => Str::after($result['reference'], $prefix.'::'))->values()->all();

            return app(Providers::class)
                ->getByPrefix($prefix)
                ->find($ids)
                ->map->toSearchResult()
                ->each(function (Result $result) use ($results) {
                    return $result
                        ->setIndex($this->index)
                        ->setRawResult($raw = $results[$result->getReference()])
                        ->setScore($raw['search_score'] ?? null);
                });
        })->sortByDesc->getScore()->values();
    }

    protected function collect($items = [])
    {
        return new DataCollection($items);
    }

    public function whereStatus(string $status)
    {
        return $this->where('status', $status);
    }

    public function getTableNameForFakeQuery()
    {
        return 'search_'.$this->index->name();
    }
}
