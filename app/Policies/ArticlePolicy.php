<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('article.view');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can('article.view');
    }

    public function create(User $user): bool
    {
        return $user->can('article.create');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('article.edit-any')
            || ($user->can('article.edit-own')
                && (int) $article->created_by === (int) $user->getKey()
                && in_array($article->status, [
                    ArticleStatus::Draft,
                    ArticleStatus::ReturnedForRevision,
                ], true));
    }

    public function review(User $user, Article $article): bool
    {
        return $user->can('article.review');
    }

    public function submit(User $user, Article $article): bool
    {
        return $user->can('article.edit-any')
            || ($user->can('article.edit-own')
                && (int) $article->created_by === (int) $user->getKey()
                && in_array($article->status, [
                    ArticleStatus::Draft,
                    ArticleStatus::ReturnedForRevision,
                ], true));
    }

    public function approve(User $user, Article $article): bool
    {
        return $user->can('article.approve');
    }

    public function returnForRevision(User $user, Article $article): bool
    {
        return $user->can('article.review');
    }

    public function schedule(User $user, Article $article): bool
    {
        return $user->can('article.schedule');
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->can('article.publish');
    }

    public function unpublish(User $user, Article $article): bool
    {
        return $user->can('article.unpublish');
    }

    public function archive(User $user, Article $article): bool
    {
        return $user->can('article.archive');
    }

    public function restoreRevision(User $user, Article $article): bool
    {
        return $user->can('article.restore-revision');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('article.delete');
    }

    public function forceDelete(User $user, Article $article): bool
    {
        return false;
    }
}
