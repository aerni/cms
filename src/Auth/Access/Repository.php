<?php

namespace Statamic\Auth\Access;

use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\Access\Rule;

class Repository
{
    /**
     * @param  class-string<Rule>  $rule
     */
    public function register(string $rule): self
    {
        $rule::register();

        return $this;
    }

    /**
     * @return Collection<int, Rule>
     */
    public function all(): Collection
    {
        return $this->bindings()
            ->map(fn (string $class) => app($class))
            ->values();
    }

    public function find(string $handle): ?Rule
    {
        if (! $class = $this->bindings()->get($handle)) {
            return null;
        }

        return app($class);
    }

    /**
     * @return Collection<int, Rule>
     */
    public function for(Context $context): Collection
    {
        return $this->all()
            ->filter(fn (Rule $rule) => $this->resources($context->resource)->contains($rule::resource()))
            ->filter(fn (Rule $rule) => $rule->operation() === $context->operation)
            ->filter(fn (Rule $rule) => $rule->appliesTo($context))
            ->values();
    }

    /**
     * @return Collection<int, class-string>
     */
    private function resources(mixed $resource): Collection
    {
        $resource = is_object($resource) ? $resource::class : $resource;

        return $this->bindings()
            ->filter(fn (string $rule) => is_a($resource, $rule::resource(), true))
            ->map(fn (string $rule) => $rule::resource())
            ->unique()
            ->values();
    }

    /**
     * @return Collection<string, class-string<Rule>>
     */
    private function bindings(): Collection
    {
        return app('statamic.access');
    }
}
