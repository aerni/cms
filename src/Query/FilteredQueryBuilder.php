<?php

namespace Statamic\Query;

use Closure;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\ForwardsCalls;
use Statamic\Contracts\Query\Builder;
use Statamic\Extensions\Pagination\LengthAwarePaginator;

class FilteredQueryBuilder implements Builder
{
    use ForwardsCalls;

    protected $builder;
    protected $filter;

    public function __construct(Builder $builder, Closure $filter)
    {
        $this->builder = $builder;
        $this->filter = $filter;
    }

    public function get($columns = ['*'])
    {
        return ($this->filter)($this->builder->get($columns));
    }

    public function count()
    {
        return $this->get()->count();
    }

    public function first()
    {
        return $this->get()->first();
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        // Unlike Ordered/Status wrappers, paginate cannot be forwarded: the inner
        // builder would page before the filter runs and produce incorrect page sizes.
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: 15;

        $items = $this->get($columns);
        $total = $items->count();
        $results = $total ? $items->forPage($page, $perPage)->values() : collect();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    protected function paginator($items, $total, $perPage, $currentPage, $options)
    {
        return app()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    public function __call($method, $parameters)
    {
        $result = $this->forwardCallTo($this->builder, $method, $parameters);

        if ($result === $this->builder) {
            return $this;
        }

        return $result;
    }
}
