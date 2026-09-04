<?php

namespace Tests\Auth\Access;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Auth\Access\Context\Context;
use Statamic\Auth\Access\Rules\Rule;
use Tests\TestCase;

class RuleConventionTest extends TestCase
{
    #[Test]
    public function it_derives_operation_from_the_class_name()
    {
        $this->assertSame('view', ViewEntry::operation());
        $this->assertSame(ConventionFakeResource::class, ViewEntry::resource());

        $this->assertSame('edit', EditEntry::operation());
        $this->assertSame(ConventionFakeResource::class, EditEntry::resource());
    }

    #[Test]
    public function it_allows_overriding_the_derived_resource()
    {
        $this->assertSame('create', CreateEntry::operation());
        $this->assertSame(ConventionFakeParentResource::class, CreateEntry::resource());
    }

    #[Test]
    public function it_allows_overriding_the_derived_operation()
    {
        $this->assertSame('publish', PreviewEntry::operation());
        $this->assertSame(ConventionFakeResource::class, PreviewEntry::resource());
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

    #[Test]
    public function it_throws_when_the_resource_is_not_set_and_not_mapped()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to determine resource type [ViewEntry] from ['.UnmappedViewEntry::class.']. Set protected static $resource.');

        UnmappedViewEntry::resource();
    }
}

class ConventionFakeResource
{
    //
}

class ConventionFakeParentResource
{
    //
}

class ViewEntry extends Rule
{
    protected static $resource = ConventionFakeResource::class;

    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class EditEntry extends Rule
{
    protected static $resource = ConventionFakeResource::class;

    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class CreateEntry extends Rule
{
    protected static $resource = ConventionFakeParentResource::class;

    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class PreviewEntry extends Rule
{
    protected static $resource = ConventionFakeResource::class;

    protected static $operation = 'publish';

    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class Broken extends Rule
{
    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class ViewUnknown extends Rule
{
    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}

class UnmappedViewEntry extends Rule
{
    public function allows(mixed $resource, Context $context): bool
    {
        return true;
    }
}
