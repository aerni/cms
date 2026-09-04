<?php

namespace Tests\Auth\Access;

use LogicException;
use Mockery;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\AccessRepository;
use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Query\Builder as QueryBuilder;
use Statamic\Contracts\Query\QueryResource;
use Statamic\Facades\Access;
use Statamic\Query\OrderedQueryBuilder;
use Statamic\Query\StatusQueryBuilder;
use Tests\TestCase;

class AccessTest extends TestCase
{
    private AccessRepository $repo;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = new AccessRepository;
    }

    #[Test]
    public function it_registers_rules_in_the_container_by_handle()
    {
        Access::register(FakeAllowRule::class);

        $this->assertInstanceOf(
            FakeAllowRule::class,
            $this->repo->find(FakeAllowRule::handle())
        );
    }

    #[Test]
    public function it_requires_an_operation_before_evaluating_access()
    {
        $resource = new FakeResource('a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An operation must be specified using can() before evaluating access.');

        Access::for(null)->resource($resource);
    }

    #[Test]
    public function it_requires_an_operation_before_applying_to_queries()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An operation must be specified using can() before evaluating access.');

        Access::for(null)->query(new FakeQuery(['a']));
    }

    #[Test]
    public function it_requires_a_resource_before_evaluating_access()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A resource instance or class string is required to evaluate access.');

        Access::for(null)->can('view')->resource(null);
    }

    #[Test]
    public function it_requires_an_access_queryable_query_before_applying_rules()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The query must implement ['.QueryResource::class.'] to apply access rules.');

        Access::for(null)->can('view')->query(new FakePlainQuery);
    }

    #[Test]
    public function it_denies_when_no_rules_are_registered()
    {
        $this->assertFalse(Access::for(null)->can('view')->resource(new FakeResource('a')));
    }

    #[Test]
    public function it_leaves_queries_unmodified_when_no_rules_are_registered()
    {
        $query = new FakeQuery(['a', 'b', 'c']);

        Access::for(null)->can('view')->query($query);

        $this->assertSame(['a', 'b', 'c'], $query->ids());
    }

    #[Test]
    public function it_registers_rules_by_class_only()
    {
        Access::register(FakeAllowRule::class);

        $this->assertTrue(Access::for(null)->can('view')->resource(new FakeResource('allowed')));
        $this->assertFalse(Access::for(null)->can('view')->resource(new FakeResource('denied')));
    }

    #[Test]
    public function it_only_runs_rules_for_the_requested_operation()
    {
        Access::register(FakeAllowRule::class);
        Access::register(FakeEditOnlyRule::class);

        $this->assertFalse(Access::for(null)->can('delete')->resource(new FakeResource('allowed')));
        $this->assertTrue(Access::for(null)->can('view')->resource(new FakeResource('allowed')));
        $this->assertFalse(Access::for(null)->can('edit')->resource(new FakeResource('denied')));
    }

    #[Test]
    public function it_only_runs_rules_that_apply_to_the_context()
    {
        Access::register(FakeCollectionSpecificRule::class);

        $this->assertTrue(Access::for(null)->can('view')->resource(new FakeResource('allowed', 'shows')));
        $this->assertFalse(Access::for(null)->can('view')->resource(new FakeResource('allowed', 'pages')));
    }

    #[Test]
    public function denial_wins_when_multiple_matching_rules_are_registered()
    {
        Access::register(FakeAllowRule::class);
        Access::register(FakeDenyRule::class);

        $this->assertFalse(Access::for(null)->can('view')->resource(new FakeResource('allowed')));
    }

    #[Test]
    public function it_applies_matching_rules_to_queries()
    {
        Access::register(FakeApplyToQueryRule::class);

        $query = new FakeQuery(['a', 'b', 'c']);

        Access::for(null)->can('view')->query($query);

        $this->assertSame(['a', 'c'], $query->ids());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function it_resolves_subject_from_wrapped_queries()
    {
        Access::register(FakeApplyToQueryRule::class);

        Access::for(null)->can('view')->query(
            new StatusQueryBuilder(new OrderedQueryBuilder(new FakeQuery(['a', 'b', 'c'])))
        );
    }

    #[Test]
    public function it_uses_context_handles_when_applying_rules_to_queries()
    {
        Access::register(FakeParentApplyToEntryQueryRule::class);

        $query = new FakeQuery(['a', 'b', 'c']);

        Access::for(null)->with(['handles' => ['access-parent-test']])->can('view')->query($query);

        $this->assertSame(['a'], $query->ids());
    }

    #[Test]
    public function it_resolves_rules_for_resource_contracts()
    {
        Access::register(FakeContractRule::class);

        $this->assertTrue(Access::for(null)->can('view')->resource(new FakeResource('allowed')));
    }

    #[Test]
    public function a_matching_rule_can_make_a_complete_decision_including_permissions()
    {
        Access::register(FakePermissionAwareRule::class);

        $withoutPermission = Mockery::mock(UserContract::class);
        $withoutPermission->shouldReceive('hasPermission')->with('view test entries')->andReturn(false);

        $withPermission = Mockery::mock(UserContract::class);
        $withPermission->shouldReceive('hasPermission')->with('view test entries')->andReturn(true);

        $this->assertFalse(Access::for($withoutPermission)->can('view')->resource(new FakeResource('allowed')));
        $this->assertTrue(Access::for($withPermission)->can('view')->resource(new FakeResource('allowed')));
    }

    #[Test]
    public function it_can_check_policy_via_fluent_can_resource()
    {
        Access::register(FakeAllowRule::class);

        $this->assertTrue(Access::for(null)->can('view')->resource(new FakeResource('allowed')));
        $this->assertFalse(Access::for(null)->can('view')->resource(new FakeResource('denied')));
    }

    #[Test]
    public function it_can_scope_queries_via_fluent_can_query()
    {
        Access::register(FakeApplyToQueryRule::class);

        $query = new FakeQuery(['a', 'b', 'c']);

        $query = Access::for(null)->can('view')->query($query);

        $this->assertSame(['a', 'c'], $query->ids());
    }
}

class FakeResourceContract
{
    //
}

class FakeResource extends FakeResourceContract
{
    public function __construct(public string $id, public string $collection = 'default')
    {
    }
}

class FakePlainQuery implements QueryBuilder
{
    //
}

class FakeQuery implements QueryBuilder, QueryResource
{
    public function __construct(private array $ids)
    {
    }

    public function subject(): string
    {
        return FakeResource::class;
    }

    public function ids(): array
    {
        return $this->ids;
    }

    public function exclude(string $id): void
    {
        $this->ids = array_values(array_filter($this->ids, fn ($value) => $value !== $id));
    }

    public function without(string $id): self
    {
        return new self(array_values(array_filter($this->ids, fn ($value) => $value !== $id)));
    }
}

class FakeAllowRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function allows(Context $context): bool
    {
        return $context->resource->id === 'allowed';
    }
}

class FakeEditOnlyRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'edit';

    public function allows(Context $context): bool
    {
        return $context->resource->id === 'allowed';
    }
}

class FakeCollectionSpecificRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function shouldApply(Context $context): bool
    {
        return $context->resource->collection === 'shows';
    }

    public function allows(Context $context): bool
    {
        return true;
    }
}

class FakeDenyRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function allows(Context $context): bool
    {
        return false;
    }
}

class FakeApplyToQueryRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function allows(Context $context): bool
    {
        return true;
    }

    public function apply(Context $context): void
    {
        if ($context->query instanceof FakeQuery) {
            $context->query->exclude('b');
        }
    }
}

class FakeParentApplyToEntryQueryRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function shouldApply(Context $context): bool
    {
        return $context->hasHandle('access-parent-test');
    }

    public function allows(Context $context): bool
    {
        return true;
    }

    public function apply(Context $context): void
    {
        if ($context->query instanceof FakeQuery) {
            $context->query->exclude('b');
            $context->query->exclude('c');
        }
    }
}

class FakeContractRule extends Rule
{
    protected static $resource = FakeResourceContract::class;
    protected static $operation = 'view';

    public function allows(Context $context): bool
    {
        return true;
    }
}

class FakePermissionAwareRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function allows(Context $context): bool
    {
        if (! $context->user?->hasPermission('view test entries')) {
            return false;
        }

        return $context->resource->id === 'allowed';
    }
}
