<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('media.view');
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can('media.view');
    }

    public function create(User $user): bool
    {
        return $user->can('media.upload');
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can('media.edit-metadata')
            || ($user->can('media.upload') && (int) $mediaAsset->uploaded_by === (int) $user->getKey());
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can('media.delete-any')
            || ($user->can('media.delete') && (int) $mediaAsset->uploaded_by === (int) $user->getKey());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaAsset $mediaAsset): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaAsset $mediaAsset): bool
    {
        return false;
    }
}
