<?php

namespace Tests\Auth\Access;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Rules\Rule;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Tests\TestCase;

class RuleConventionTest extends TestCase
{
    #[Test]
    public function it_derives_operation_and_resource_from_the_class_name()
    {
        $this->assertSame('view', ViewEntry::operation());
        $this->assertSame(Entry::class, ViewEntry::resource());

        $this->assertSame('edit', EditEntry::operation());
        $this->assertSame(Entry::class, EditEntry::resource());
    }

    #[Test]
    public function it_allows_overriding_the_derived_resource()
    {
        $this->assertSame('create', CreateEntry::operation());
        $this->assertSame(Collection::class, CreateEntry::resource());
    }

    #[Test]
    public function it_allows_overriding_the_derived_operation()
    {
        $this->assertSame('publish', PreviewEntry::operation());
        $this->assertSame(Entry::class, PreviewEntry::resource());
    }

    #[Test]
    public function it_throws_when_the_resource_cannot_be_derived_from_the_class_name()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to determine resource from [Broken]. Set protected static $resource.');

        Broken::resource();
    }

    #[Test]
    public function it_throws_when_the_resource_type_is_unknown()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to determine resource type [Unknown] from ['.ViewUnknown::class.']. Set protected static $resource.');

        ViewUnknown::resource();
    }
}

class ViewEntry extends Rule
{
    public function allows(Context $context): bool
    {
        return true;
    }
}

class EditEntry extends Rule
{
    public function allows(Context $context): bool
    {
        return true;
    }
}

class CreateEntry extends Rule
{
    protected static $resource = Collection::class;

    public function allows(Context $context): bool
    {
        return true;
    }
}

class PreviewEntry extends Rule
{
    protected static $operation = 'publish';

    public function allows(Context $context): bool
    {
        return true;
    }
}

class Broken extends Rule
{
    public function allows(Context $context): bool
    {
        return true;
    }
}

class ViewUnknown extends Rule
{
    public function allows(Context $context): bool
    {
        return true;
    }
}
