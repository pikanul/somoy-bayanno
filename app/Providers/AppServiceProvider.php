<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Author;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\HomepageItem;
use App\Models\HomepageSection;
use App\Models\LiveStream;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use App\Observers\ArticleObserver;
use App\Observers\BreakingNewsObserver;
use App\Observers\CategoryObserver;
use App\Observers\HomepageItemObserver;
use App\Observers\HomepageSectionObserver;
use App\Observers\UserObserver;
use App\Policies\ActivityLogPolicy;
use App\Policies\ArticlePolicy;
use App\Policies\AuthorPolicy;
use App\Policies\BreakingNewsPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\GalleryPolicy;
use App\Policies\HomepageSectionPolicy;
use App\Policies\LiveStreamPolicy;
use App\Policies\MediaAssetPolicy;
use App\Policies\TagPolicy;
use App\Policies\TopicPolicy;
use App\Policies\UserPolicy;
use App\Policies\VideoPolicy;
use App\Services\ActivityLogger;
use App\Support\Security\Rbac;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Author::class, AuthorPolicy::class);
        Gate::policy(BreakingNews::class, BreakingNewsPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Gallery::class, GalleryPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(HomepageSection::class, HomepageSectionPolicy::class);
        Gate::policy(LiveStream::class, LiveStreamPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Topic::class, TopicPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Video::class, VideoPolicy::class);

        RateLimiter::for('search', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        Gate::before(function (User $user, string $ability): ?bool {
            if (in_array($ability, ['delete', 'disable', 'assignRole', 'forceDelete'], true)) {
                return null;
            }

            return $user->hasRole(Rbac::SUPER_ADMIN) ? true : null;
        });

        User::observe(UserObserver::class);
        Article::observe(ArticleObserver::class);
        BreakingNews::observe(BreakingNewsObserver::class);
        Category::observe(CategoryObserver::class);
        HomepageItem::observe(HomepageItemObserver::class);
        HomepageSection::observe(HomepageSectionObserver::class);

        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                ])->saveQuietly();

                app(ActivityLogger::class)->login($event->user);
            }
        });

        Event::listen(function (Failed $event): void {
            app(ActivityLogger::class)->failedLogin([
                'email' => $event->credentials['email'] ?? null,
            ]);
        });
    }
}
