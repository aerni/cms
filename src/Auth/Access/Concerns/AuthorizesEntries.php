<?php

namespace Statamic\Auth\Access\Concerns;

use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Sites\Site;

trait AuthorizesEntries
{
    protected function userCanAccessSite(?User $user, Site $site): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('view', $site);
    }

    protected function hasAnotherAuthor(?User $user, Entry $entry): bool
    {
        if (! $user || $entry->blueprint()->hasField('author') === false) {
            return false;
        }

        return ! $entry->authors()->contains($user->id());
    }

    protected function canEdit(?User $user, Entry $entry): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->userCanAccessSite($user, $entry->site())) {
            return false;
        }

        if ($this->hasAnotherAuthor($user, $entry)) {
            return $user->hasPermission("edit other authors {$entry->collectionHandle()} entries");
        }

        return $user->hasPermission("edit {$entry->collectionHandle()} entries");
    }
}
