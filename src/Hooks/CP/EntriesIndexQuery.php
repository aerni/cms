<?php

namespace Statamic\Hooks\CP;

use Statamic\Facades\Access;
use Statamic\Facades\User;
use Statamic\Support\Traits\Hookable;

class EntriesIndexQuery
{
    use Hookable;

    public function __construct(private $query, private $collection)
    {
        //
    }

    public function paginate(?int $perPage)
    {
        $payload = $this->runHooksWith('query', [
            'query' => $this->query,
            'collection' => $this->collection,
        ]);

        $query = $payload->query;

        Access::for(User::current())->can('view')->query($query);

        return $query->paginate($perPage);
    }
}
