<?php

namespace App\Policies;

use App\Models\LiveStream;
use App\Models\User;

class LiveStreamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('video.view');
    }

    public function view(User $user, LiveStream $liveStream): bool
    {
        return $user->can('video.view');
    }

    public function create(User $user): bool
    {
        return $user->can('video.create');
    }

    public function update(User $user, LiveStream $liveStream): bool
    {
        return $user->can('video.edit-any')
            || ($user->can('video.edit-own') && (int) $liveStream->created_by === (int) $user->getKey());
    }

    public function delete(User $user, LiveStream $liveStream): bool
    {
        return $user->can('video.delete');
    }

    public function restore(User $user, LiveStream $liveStream): bool
    {
        return false;
    }

    public function forceDelete(User $user, LiveStream $liveStream): bool
    {
        return false;
    }
}
