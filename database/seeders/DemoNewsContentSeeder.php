<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Enums\GalleryStatus;
use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Video;
use App\Services\PublicContentCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoNewsContentSeeder extends Seeder
{
    private const SourceName = 'Demo News Content';

    public function run(): void
    {
        $user = User::query()->first();
        $media = $this->media($user);

        $this->articles($media, $user);
        $this->videos($user);
        $this->galleries($media, $user);

        app(PublicContentCache::class)->flushPublicContent();
    }

    /** @return Collection<string, MediaAsset> */
    private function media(?User $user): Collection
    {
        return collect([
            'hero-metro' => ['width' => 970, 'height' => 520, 'alt' => 'ঢাকার মেট্রোরেল'],
            'leader' => ['width' => 300, 'height' => 220, 'alt' => 'প্রধান উপদেষ্টার সংবাদ'],
            'biden' => ['width' => 300, 'height' => 220, 'alt' => 'আন্তর্জাতিক সংবাদ'],
            'flood' => ['width' => 300, 'height' => 220, 'alt' => 'বৃষ্টিতে জলাবদ্ধতা'],
            'cricket' => ['width' => 300, 'height' => 220, 'alt' => 'বাংলাদেশ ক্রিকেট'],
            'martyrs' => ['width' => 320, 'height' => 200, 'alt' => 'জাতীয় স্মৃতিসৌধ'],
            'parliament' => ['width' => 320, 'height' => 200, 'alt' => 'জাতীয় সংসদ ভবন'],
            'port' => ['width' => 320, 'height' => 200, 'alt' => 'বন্দর ও অর্থনীতি'],
            'football' => ['width' => 320, 'height' => 200, 'alt' => 'ফুটবল মাঠ'],
            'ai' => ['width' => 320, 'height' => 200, 'alt' => 'কৃত্রিম বুদ্ধিমত্তা'],
            'yoga' => ['width' => 320, 'height' => 200, 'alt' => 'স্বাস্থ্যকর জীবনযাপন'],
            'cox' => ['width' => 320, 'height' => 180, 'alt' => 'কক্সবাজারের সন্ধ্যা'],
            'lilies' => ['width' => 320, 'height' => 180, 'alt' => 'শাপলার বিল'],
            'dhaka' => ['width' => 320, 'height' => 180, 'alt' => 'ঢাকার পুরোনো শহর'],
        ])->mapWithKeys(function (array $item, string $key) use ($user): array {
            $asset = MediaAsset::query()->updateOrCreate(
                ['original_name' => "demo-home-{$key}.png", 'source_name' => self::SourceName],
                [
                    'type' => 'image',
                    'storage_disk' => null,
                    'path' => null,
                    'external_url' => url("/demo-home/{$key}.png"),
                    'mime_type' => 'image/png',
                    'file_size' => null,
                    'width' => $item['width'],
                    'height' => $item['height'],
                    'alt_text' => $item['alt'],
                    'caption' => $item['alt'],
                    'credit' => 'Demo asset',
                    'photographer' => 'Demo',
                    'source_url' => url("/demo-home/{$key}.png"),
                    'copyright' => 'Demo content for UI testing',
                    'uploaded_by' => $user?->getKey(),
                ],
            );

            return [$key => $asset];
        });
    }

    /** @param Collection<string, MediaAsset> $media */
    private function articles(Collection $media, ?User $user): void
    {
        $articles = [
            ['national', 'hero-metro', 'রাজধানীতে মেট্রোরেলের যাত্রা আরও সহজ হচ্ছে, যুক্ত হচ্ছে নতুন ২ স্টেশন', true],
            ['politics', 'leader', 'সহযোগিতায় সরকারের অগ্রগতিতে প্রধান উপদেষ্টার বার্তা', false],
            ['international', 'biden', 'ইউক্রেনকে আরও অস্ত্র সহায়তা দেবে যুক্তরাষ্ট্র', false],
            ['national', 'flood', 'চট্টগ্রামে ভারী বৃষ্টিতে জলাবদ্ধতা, দুর্ভোগে মানুষ', false],
            ['sports', 'cricket', 'বিশ্বকাপকে সামনে রেখে বাংলাদেশের স্কোয়াড ঘোষণা', false],
            ['national', 'martyrs', 'রাষ্ট্র সংস্কারে সবাইকে ঐক্যবদ্ধ থাকার আহ্বান', false],
            ['politics', 'parliament', 'নতুন রাজনৈতিক সমঝোতার সম্ভাবনা', false],
            ['economy', 'port', 'রপ্তানি আয়ে নতুন রেকর্ড, বাড়ছে সম্ভাবনা', false],
            ['sports', 'football', 'বিশ্বকাপ প্রস্তুতি: বাংলাদেশ প্রস্তুত', false],
            ['technology', 'ai', 'কৃত্রিম বুদ্ধিমত্তা বদলে দিচ্ছে আমাদের জীবন', false],
            ['lifestyle', 'yoga', 'মানসিক সুস্থতায় নিয়মিত ব্যায়ামের গুরুত্ব', false],
            ['entertainment', 'cox', 'সাগরপাড়ে উৎসব ঘিরে পর্যটকদের ভিড়', false],
            ['opinion', 'dhaka', 'নগর জীবনে পরিকল্পিত উন্নয়নের প্রয়োজনীয়তা', false],
        ];

        foreach ($articles as $index => [$categorySlug, $imageKey, $headline, $featured]) {
            $category = Category::query()->where('slug', $categorySlug)->first();
            $slug = "demo-{$categorySlug}-news-{$index}";

            $article = Article::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => ArticleType::Standard,
                    'headline_bn' => $headline,
                    'headline_en' => Str::headline($slug),
                    'short_headline_bn' => Str::limit($headline, 48),
                    'subheadline_bn' => 'UI পরীক্ষার জন্য তৈরি নমুনা সংবাদ',
                    'summary_bn' => 'দৈনিক সময় বায়ান্নের হোম পেজ ও বিভাগীয় পাতা পরীক্ষা করার জন্য এই নমুনা সংবাদটি তৈরি করা হয়েছে।',
                    'body_bn' => '<p>এই সংবাদটি পরীক্ষামূলক কনটেন্ট। admin login থেকে Article মেনুতে গিয়ে শিরোনাম, ছবি, ভিডিও লিংক, ক্যাটাগরি ও প্রকাশনার তথ্য পরিবর্তন বা মুছে ফেলা যাবে।</p><p>পাতার ভিজ্যুয়াল লেআউট, বিভাগীয় লিংক, সংবাদ কার্ড এবং বিস্তারিত পাতা যাচাই করার জন্য এখানে পর্যাপ্ত লেখা রাখা হয়েছে।</p>',
                    'primary_category_id' => $category?->getKey(),
                    'reporter_name' => 'ডেমো প্রতিবেদক',
                    'location' => 'ঢাকা',
                    'source_name' => self::SourceName,
                    'source_url' => null,
                    'featured_media_id' => $media->get($imageKey)?->getKey(),
                    'image_caption' => $media->get($imageKey)?->caption,
                    'image_credit' => 'Demo asset',
                    'video_url' => $featured ? 'https://www.youtube.com/watch?v=jNQXAC9IVRw' : null,
                    'status' => ArticleStatus::Published,
                    'visibility' => ArticleVisibility::Public,
                    'comments_enabled' => true,
                    'is_breaking' => $index < 3,
                    'is_featured' => $featured,
                    'published_at' => now()->subMinutes($index + 5),
                    'seo_title' => $headline,
                    'seo_description' => 'পরীক্ষামূলক সংবাদ কনটেন্ট',
                    'created_by' => $user?->getKey(),
                    'updated_by' => $user?->getKey(),
                    'published_by' => $user?->getKey(),
                ],
            );

            if ($category) {
                $article->categories()->syncWithoutDetaching([
                    $category->getKey() => ['is_primary' => true, 'sort_order' => 0],
                ]);
            }
        }
    }

    private function videos(?User $user): void
    {
        $category = Category::query()->where('slug', 'national')->first();
        $videos = [
            ['পদ্মা সেতুর পরিবর্তন: বাংলাদেশের উন্নয়নের নতুন অধ্যায়', 'padma', 'https://www.youtube.com/watch?v=jNQXAC9IVRw', 'jNQXAC9IVRw', 154],
            ['বিশ্বকাপকে ঘিরে ক্রিকেট উন্মাদনা', 'rally', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ', 195],
            ['সময় বায়ান্ন বিশেষ সাক্ষাৎকার: ড. মুহাম্মদ ইউনূস', 'yunus-video', 'https://www.youtube.com/watch?v=9bZkp7q19f0', '9bZkp7q19f0', 262],
        ];

        foreach ($videos as $index => [$title, $imageKey, $url, $providerId, $duration]) {
            Video::query()->updateOrCreate(
                ['title_bn' => $title, 'video_url' => $url],
                [
                    'description_bn' => 'পরীক্ষার জন্য যুক্ত করা public video link। admin login থেকে Video মেনুতে গিয়ে পরিবর্তন বা delete করা যাবে।',
                    'provider' => VideoProvider::YouTube,
                    'provider_video_id' => $providerId,
                    'thumbnail' => url("/demo-home/{$imageKey}.png"),
                    'duration' => $duration,
                    'category_id' => $category?->getKey(),
                    'status' => VideoStatus::Published,
                    'published_at' => now()->subMinutes($index + 20),
                    'seo_title' => $title,
                    'seo_description' => 'পরীক্ষামূলক ভিডিও কনটেন্ট',
                    'created_by' => $user?->getKey(),
                    'updated_by' => $user?->getKey(),
                ],
            );
        }
    }

    /** @param Collection<string, MediaAsset> $media */
    private function galleries(Collection $media, ?User $user): void
    {
        $category = Category::query()->where('slug', 'lifestyle')->first();
        $galleries = [
            ['কক্সবাজারের অপূর্ব সন্ধ্যা', 'cox'],
            ['শাপলার দেশে বাংলাদেশ', 'lilies'],
            ['ঢাকার পুরোনো শহরের রঙ', 'dhaka'],
        ];

        foreach ($galleries as $index => [$title, $imageKey]) {
            Gallery::query()->updateOrCreate(
                ['slug' => "demo-gallery-{$index}"],
                [
                    'title' => $title,
                    'description' => 'ছবিঘর পাতা পরীক্ষা করার জন্য তৈরি নমুনা গ্যালারি। admin login থেকে Gallery মেনুতে গিয়ে edit/delete করা যাবে।',
                    'cover_image_id' => $media->get($imageKey)?->getKey(),
                    'photographer' => 'Demo',
                    'category_id' => $category?->getKey(),
                    'status' => GalleryStatus::Published,
                    'published_at' => now()->subMinutes($index + 40),
                    'seo_title' => $title,
                    'seo_description' => 'পরীক্ষামূলক ছবিঘর কনটেন্ট',
                    'created_by' => $user?->getKey(),
                    'updated_by' => $user?->getKey(),
                ],
            );
        }
    }
}
