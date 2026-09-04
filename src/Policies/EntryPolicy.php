<?php

namespace Statamic\Policies;

use Statamic\Contracts\Entries\Collection;
use Statamic\Facades\Access;
use Statamic\Facades\User;

class EntryPolicy
{
    use Concerns\HasMultisitePolicy;

    public function before($user)
    {
        $user = User::fromUser($user);

        if ($user->isSuper() || $user->hasPermission('configure collections')) {
            return true;
        }
    }

    public function index($user)
    {
        //
    }

    public function view($user, $entry)
    {
        $user = User::fromUser($user);

        return Access::for($user)->can('view')->resource($entry);
    }

    public function edit($user, $entry)
    {
        $user = User::fromUser($user);

        return Access::for($user)->can('edit')->resource($entry);
    }

    public function editOtherAuthorsEntries($user, $collection, $blueprint = null)
    {
        $blueprint = $blueprint ?? $collection->entryBlueprint();

        if ($blueprint->hasField('author') === false) {
            return true;
        }

        return $user->hasPermission("edit other authors {$collection->handle()} entries");
    }

    public function update($user, $entry)
    {
        return $this->edit($user, $entry);
    }

    public function create($user, $collection, $site = null)
    {
        $user = User::fromUser($user);

        if ($site && (! $collection->sites()->contains($site->handle()) || ! $this->userCanAccessSite($user, $site))) {
            return false;
        }

        return Access::for($user)->can('create')->resource($collection);
    }

    public function store($user, $collection, $site = null)
    {
        return $this->create($user, $collection, $site);
    }

    public function delete($user, $entry)
    {
        $user = User::fromUser($user);

        return Access::for($user)->can('delete')->resource($entry);
    }

    public function publish($user, $entry)
    {
        $user = User::fromUser($user);

        if ($entry instanceof Collection) {
            return $user->hasPermission("publish {$entry->handle()} entries");
        }

        return Access::for($user)->can('publish')->resource($entry);
    }
}
