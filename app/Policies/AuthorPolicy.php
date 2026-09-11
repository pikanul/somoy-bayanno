<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\User;

class AuthorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('author.view');
    }

    public function view(User $user, Author $author): bool
    {
        return $user->can('author.view');
    }

    public function create(User $user): bool
    {
        return $user->can('author.create');
    }

    public function update(User $user, Author $author): bool
    {
        return $user->can('author.edit-any');
    }

    public function delete(User $user, Author $author): bool
    {
        return $user->can('author.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Author $author): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Author $author): bool
    {
        return $user->can('author.edit-any');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
