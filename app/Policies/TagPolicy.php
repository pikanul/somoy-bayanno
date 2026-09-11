<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tag.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('tag.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tag.create');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('tag.edit-any');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('tag.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Tag $tag): bool
    {
        return $user->can('tag.edit-any');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
