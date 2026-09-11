<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('video.view');
    }

    public function view(User $user, Video $video): bool
    {
        return $user->can('video.view');
    }

    public function create(User $user): bool
    {
        return $user->can('video.create');
    }

    public function update(User $user, Video $video): bool
    {
        return $user->can('video.edit-any')
            || ($user->can('video.edit-own') && (int) $video->created_by === (int) $user->getKey());
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->can('video.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Video $video): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Video $video): bool
    {
        return false;
    }
}
