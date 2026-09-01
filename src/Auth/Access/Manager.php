<?php

namespace Statamic\Auth\Access;

use Closure;
use LogicException;
use Statamic\Contracts\Auth\Access\Rule;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Query\Builder;

class Manager
{
    public function __construct(
        protected Repository $repository,
        protected ?Closure $userResolver = null,
    ) {
    }

    /**
     * @param  class-string<Rule>  $rule
     */
    public function register(string $rule): self
    {
        $this->repository->register($rule);

        return $this;
    }

    public function forUser(?User $user): self
    {
        return new self(
            $this->repository,
            fn () => $user,
        );
    }

    public function allows(string $operation, mixed $resource): bool
    {
        $context = $this->context($operation, $resource);

        $rules = $this->repository->for($context);

        if ($rules->isEmpty()) {
            return false;
        }

        return $rules->every(fn ($rule) => $rule->allows($context));
    }

    public function restrictQuery(Builder $query, mixed $resource, string $operation = 'view'): Builder
    {
        $context = $this->context($operation, $resource);

        $rules = $this->repository->for($context);

        if ($rules->isEmpty()) {
            return $query;
        }

        return $rules->reduce(
            fn ($query, $rule) => $rule->restrictQuery($context, $query),
            $query,
        );
    }

    protected function context(string $operation, mixed $resource): Context
    {
        if (! is_object($resource) && ! (is_string($resource) && class_exists($resource))) {
            throw new \LogicException('A resource instance or class string is required to evaluate access.');
        }

        return new Context($this->resolveUser(), $operation, $resource);
    }

    protected function resolveUser(): ?User
    {
        if ($this->userResolver === null) {
            throw new LogicException('A user must be specified using forUser() before evaluating access.');
        }

        return ($this->userResolver)();
    }
}
