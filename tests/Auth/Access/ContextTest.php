<?php

namespace Tests\Auth\Access;

use Illuminate\Support\Collection;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Query\Builder as QueryBuilder;
use Statamic\Contracts\Query\QueryResource;
use Tests\TestCase;

class ContextTest extends TestCase
{
    #[Test]
    public function it_resolves_context_for_a_resource()
    {
        $resource = new ContextFakeResource;

        $context = Context::fromResource($resource, 'view');

        $this->assertNull($context->user);
        $this->assertSame('view', $context->operation);
        $this->assertSame(ContextFakeResource::class, $context->subject);
        $this->assertInstanceOf(Collection::class, $context->data);
        $this->assertSame([], $context->data->all());
    }

    #[Test]
    public function it_merges_additional_data_onto_the_context()
    {
        $context = Context::fromResource(
            new ContextFakeResource,
            'view',
            data: ['site' => 'en', 'group' => 'shows'],
        );

        $this->assertSame([
            'site' => 'en',
            'group' => 'shows',
        ], $context->data->all());
        $this->assertSame('en', $context->data->get('site'));
    }

    #[Test]
    public function it_resolves_context_for_a_query()
    {
        $query = new ContextFakeQuery(['a', 'b']);

        $context = Context::fromQuery($query, 'view');

        $this->assertSame(ContextFakeResource::class, $context->subject);
        $this->assertSame([], $context->data->all());
    }

    #[Test]
    public function it_allows_data_to_be_passed_for_a_query()
    {
        $context = Context::fromQuery(
            new ContextFakeQuery(['a']),
            'view',
            data: ['group' => 'shows'],
        );

        $this->assertSame(['group' => 'shows'], $context->data->all());
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
