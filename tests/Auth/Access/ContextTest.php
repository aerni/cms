<?php

namespace Tests\Auth\Access;

use Illuminate\Support\Collection;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Query\Builder as QueryBuilder;
use Statamic\Contracts\Query\QueryResource;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Tests\TestCase;

class ContextTest extends TestCase
{
    #[Test]
    public function it_resolves_context_for_a_resource()
    {
        CollectionFacade::make('shows')->save();
        $entry = EntryFacade::make()->collection('shows')->slug('a');

        $context = Context::fromResource($entry, 'view');

        $this->assertNull($context->user);
        $this->assertSame('view', $context->operation);
        $this->assertSame($entry, $context->resource);
        $this->assertNull($context->query);
        $this->assertInstanceOf(Collection::class, $context->data);
        $this->assertSame(['shows'], $context->data->get('handles'));
        $this->assertTrue($context->hasHandle('shows'));
        $this->assertSame(['handles' => ['shows']], $context->data->all());
    }

    #[Test]
    public function it_merges_additional_data_onto_the_context()
    {
        CollectionFacade::make('shows')->save();
        $entry = EntryFacade::make()->collection('shows')->slug('a');

        $context = Context::fromResource($entry, 'view', data: ['site' => 'en']);

        $this->assertSame([
            'handles' => ['shows'],
            'site' => 'en',
        ], $context->data->all());
        $this->assertSame('en', $context->data->get('site'));
        $this->assertTrue($context->data->has('site'));
    }

    #[Test]
    public function it_allows_data_to_override_resolved_handles()
    {
        CollectionFacade::make('shows')->save();
        $entry = EntryFacade::make()->collection('shows')->slug('a');

        $context = Context::fromResource($entry, 'view', data: ['handles' => ['movies']]);

        $this->assertSame(['movies'], $context->data->get('handles'));
    }

    #[Test]
    public function it_resolves_context_for_a_query()
    {
        $query = new ContextFakeQuery(['a', 'b']);

        $context = Context::fromQuery($query, 'view');

        $this->assertSame(ContextFakeResource::class, $context->resource);
        $this->assertSame($query, $context->query);
        $this->assertSame([], $context->data->get('handles'));
    }

    #[Test]
    public function it_allows_data_to_provide_handles_for_a_query()
    {
        $context = Context::fromQuery(
            new ContextFakeQuery(['a']),
            'view',
            data: ['handles' => ['shows', 'movies']],
        );

        $this->assertSame(['shows', 'movies'], $context->data->get('handles'));
        $this->assertTrue($context->hasAnyHandle(['shows', 'pages']));
        $this->assertFalse($context->hasHandle('pages'));
    }

    #[Test]
    public function it_requires_queries_to_implement_query_resource()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The query must implement ['.QueryResource::class.'] to apply access rules.');

        Context::fromQuery(new ContextFakePlainQuery, 'view');
    }
}

class ContextFakeResource
{
    //
}

class ContextFakePlainQuery implements QueryBuilder
{
    //
}

class ContextFakeQuery implements QueryBuilder, QueryResource
{
    public function __construct(private array $ids)
    {
    }

    public function subject(): string
    {
        return ContextFakeResource::class;
    }
}
