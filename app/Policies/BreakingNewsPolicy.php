<?php

namespace App\Policies;

use App\Models\BreakingNews;
use App\Models\User;

class BreakingNewsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('breaking-news.view');
    }

    public function view(User $user, BreakingNews $breakingNews): bool
    {
        return $user->can('breaking-news.view');
    }

    public function create(User $user): bool
    {
        return $user->can('breaking-news.create');
    }

    public function update(User $user, BreakingNews $breakingNews): bool
    {
        return $user->can('breaking-news.update');
    }

    public function delete(User $user, BreakingNews $breakingNews): bool
    {
        return $user->can('breaking-news.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BreakingNews $breakingNews): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BreakingNews $breakingNews): bool
    {
        return false;
    }
}
