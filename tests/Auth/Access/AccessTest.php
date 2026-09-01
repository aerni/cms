<?php

namespace Tests\Auth\Access;

use LogicException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\Context;
use Statamic\Auth\Access\Repository;
use Statamic\Auth\Access\Rule;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Query\Builder as QueryBuilder;
use Statamic\Facades\Access;
use Tests\TestCase;

class AccessTest extends TestCase
{
    private Repository $repo;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = new Repository;
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
    public function it_requires_a_user_before_evaluating_access()
    {
        $resource = new FakeResource('a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A user must be specified using forUser() before evaluating access.');

        Access::allows('view', $resource);
    }

    #[Test]
    public function it_requires_a_user_before_applying_to_queries()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A user must be specified using forUser() before evaluating access.');

        Access::applyRulesTo(new FakeQuery(['a']), new FakeResource('a'));
    }

    #[Test]
    public function it_requires_a_resource_before_evaluating_access()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A resource instance or class string is required to evaluate access.');

        Access::forUser(null)->allows('view', null);
    }

    #[Test]
    public function it_requires_a_resource_before_applying_to_queries()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A resource instance or class string is required to evaluate access.');

        Access::forUser(null)->applyRulesTo(new FakeQuery(['a']), null);
    }

    #[Test]
    public function it_denies_when_no_rules_are_registered()
    {
        $this->assertFalse(Access::forUser(null)->allows('view', new FakeResource('a')));
    }

    #[Test]
    public function it_leaves_queries_unmodified_when_no_rules_are_registered()
    {
        $query = new FakeQuery(['a', 'b', 'c']);

        Access::forUser(null)->applyRulesTo($query, new FakeResource('a'));

        $this->assertSame(['a', 'b', 'c'], $query->ids());
    }

    #[Test]
    public function it_registers_rules_by_class_only()
    {
        Access::register(FakeAllowRule::class);

        $this->assertTrue(Access::forUser(null)->allows('view', new FakeResource('allowed')));
        $this->assertFalse(Access::forUser(null)->allows('view', new FakeResource('denied')));
    }

    #[Test]
    public function it_only_runs_rules_for_the_requested_operation()
    {
        Access::register(FakeAllowRule::class);
        Access::register(FakeEditOnlyRule::class);

        $this->assertFalse(Access::forUser(null)->allows('delete', new FakeResource('allowed')));
        $this->assertTrue(Access::forUser(null)->allows('view', new FakeResource('allowed')));
        $this->assertFalse(Access::forUser(null)->allows('edit', new FakeResource('denied')));
    }

    #[Test]
    public function it_only_runs_rules_that_apply_to_the_context()
    {
        Access::register(FakeCollectionSpecificRule::class);

        $this->assertTrue(Access::forUser(null)->allows('view', new FakeResource('allowed', 'shows')));
        $this->assertFalse(Access::forUser(null)->allows('view', new FakeResource('allowed', 'pages')));
    }

    #[Test]
    public function denial_wins_when_multiple_matching_rules_are_registered()
    {
        Access::register(FakeAllowRule::class);
        Access::register(FakeDenyRule::class);

        $this->assertFalse(Access::forUser(null)->allows('view', new FakeResource('allowed')));
    }

    #[Test]
    public function it_applies_matching_rules_to_queries()
    {
        Access::register(FakeApplyToQueryRule::class);

        $query = new FakeQuery(['a', 'b', 'c']);

        Access::forUser(null)->applyRulesTo($query, new FakeResource('a'));

        $this->assertSame(['a', 'c'], $query->ids());
    }

    #[Test]
    public function it_passes_parent_to_rules_when_applying_to_queries()
    {
        Access::register(FakeParentApplyToQueryRule::class);

        $parent = new FakeResource('a', 'shows');
        $query = new FakeQuery(['a', 'b', 'c']);

        Access::forUser(null)->applyRulesTo(
            $query,
            new FakeResource('a'),
            'view',
            $parent,
        );

        $this->assertSame(['a'], $query->ids());
    }

    #[Test]
    public function it_resolves_rules_for_resource_contracts()
    {
        Access::register(FakeContractRule::class);

        $this->assertTrue(Access::forUser(null)->allows('view', new FakeResource('allowed')));
    }

    #[Test]
    public function a_matching_rule_can_make_a_complete_decision_including_permissions()
    {
        Access::register(FakePermissionAwareRule::class);

        $withoutPermission = Mockery::mock(UserContract::class);
        $withoutPermission->shouldReceive('hasPermission')->with('view test entries')->andReturn(false);

        $withPermission = Mockery::mock(UserContract::class);
        $withPermission->shouldReceive('hasPermission')->with('view test entries')->andReturn(true);

        $this->assertFalse(Access::forUser($withoutPermission)->allows('view', new FakeResource('allowed')));
        $this->assertTrue(Access::forUser($withPermission)->allows('view', new FakeResource('allowed')));
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

class FakeQuery implements QueryBuilder
{
    public function __construct(private array $ids)
    {
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

    public function appliesTo(Context $context): bool
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

    public function apply(QueryBuilder $query, Context $context): void
    {
        if ($query instanceof FakeQuery) {
            $query->exclude('b');
        }
    }
}

class FakeParentApplyToQueryRule extends Rule
{
    protected static $resource = FakeResource::class;
    protected static $operation = 'view';

    public function appliesTo(Context $context): bool
    {
        return $context->parent?->collection === 'shows';
    }

    public function allows(Context $context): bool
    {
        return true;
    }

    public function apply(QueryBuilder $query, Context $context): void
    {
        if ($query instanceof FakeQuery) {
            $query->exclude('b');
            $query->exclude('c');
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
