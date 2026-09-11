<?php

namespace App\Policies;

use App\Models\HomepageSection;
use App\Models\User;

class HomepageSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('homepage.manage');
    }

    public function view(User $user, HomepageSection $homepageSection): bool
    {
        return $user->can('homepage.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('homepage.manage');
    }

    public function update(User $user, HomepageSection $homepageSection): bool
    {
        return $user->can('homepage.manage');
    }

    public function delete(User $user, HomepageSection $homepageSection): bool
    {
        return $user->can('homepage.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HomepageSection $homepageSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HomepageSection $homepageSection): bool
    {
        return false;
    }
}
