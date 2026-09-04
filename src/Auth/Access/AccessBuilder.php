<?php

namespace Statamic\Auth\Access;

use LogicException;
use Statamic\Auth\Access\Context\Context;
use Statamic\Contracts\Auth\Access\Rule;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Query\Builder;
use Statamic\Facades\Access;

class AccessBuilder
{
    protected ?User $user = null;

    protected ?string $operation = null;

    protected array $data = [];

    public function user(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function with(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function can(string $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function resource(mixed $resource): bool
    {
        $context = Context::fromResource($resource, $this->operation(), $this->user, $this->data);

        $rules = Access::rules($context);

        if ($rules->isEmpty()) {
            return false;
        }

        return $rules->every(fn ($rule) => $rule->allows($resource, $context));
    }

    public function query(Builder $query): Builder
    {
        $context = Context::fromQuery($query, $this->operation(), $this->user, $this->data);

        $rules = Access::rules($context);

        if ($rules->isEmpty()) {
            return $query->whereIn('id', []);
        }

        $rules->each(function (Rule $rule) use ($context, $query) {
            $query->where(fn (Builder $ruleQuery) => $rule->apply($ruleQuery, $context));
        });

        return $query;
    }

    public function on(mixed $target): Builder|bool
    {
        return $target instanceof Builder
            ? $this->query($target)
            : $this->resource($target);
    }

    private function operation(): string
    {
        if ($this->operation === null) {
            throw new LogicException('An operation must be specified using can() before evaluating access.');
        }

        return $this->operation;
    }
}
