<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('topic.view');
    }

    public function view(User $user, Topic $topic): bool
    {
        return $user->can('topic.view');
    }

    public function create(User $user): bool
    {
        return $user->can('topic.create');
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->can('topic.edit-any');
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->can('topic.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Topic $topic): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Topic $topic): bool
    {
        return $user->can('topic.edit-any');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
