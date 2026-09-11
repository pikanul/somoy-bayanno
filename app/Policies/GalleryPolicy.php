<?php

namespace App\Policies;

use App\Models\Gallery;
use App\Models\User;

class GalleryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gallery.view');
    }

    public function view(User $user, Gallery $gallery): bool
    {
        return $user->can('gallery.view');
    }

    public function create(User $user): bool
    {
        return $user->can('gallery.create');
    }

    public function update(User $user, Gallery $gallery): bool
    {
        return $user->can('gallery.edit-any')
            || ($user->can('gallery.edit-own') && (int) $gallery->created_by === (int) $user->getKey());
    }

    public function delete(User $user, Gallery $gallery): bool
    {
        return $user->can('gallery.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Gallery $gallery): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Gallery $gallery): bool
    {
        return false;
    }
}
