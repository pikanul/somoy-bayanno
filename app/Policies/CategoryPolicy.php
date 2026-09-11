<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('category.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('category.view');
    }

    public function create(User $user): bool
    {
        return $user->can('category.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('category.edit-any');
    }

    public function delete(User $user, Category $category): Response
    {
        if (! $user->can('category.delete')) {
            return Response::deny();
        }

        if ($category->children()->exists()) {
            return Response::deny('Categories with child categories cannot be deleted.');
        }

        return Response::allow();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->can('category.edit-any');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $user->can('category.reorder');
    }
}
