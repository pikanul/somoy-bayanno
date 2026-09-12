<?php

namespace Database\Seeders;

use App\Models\LiveStream;
use Illuminate\Database\Seeder;

class LiveStreamSeeder extends Seeder
{
    public function run(): void
    {
        LiveStream::query()->updateOrCreate(
            ['slug' => 'somoy-bayanno-live'],
            [
                'title_bn' => 'দৈনিক সময় বায়ান্ন লাইভ',
                'provider' => 'youtube',
                'stream_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                'embed_url' => 'https://www.youtube-nocookie.com/embed/jNQXAC9IVRw',
                'poster_url' => asset('demo-home/logo.png'),
                'description_bn' => 'লাইভ স্ট্রিম পরীক্ষার জন্য নমুনা ভিডিও। admin panel থেকে Live Streams মেনুতে গিয়ে লিংক, শিরোনাম, স্ট্যাটাস edit/delete করা যাবে।',
                'is_active' => true,
                'autoplay' => true,
                'muted' => true,
                'status_text' => 'LIVE NOW',
                'sort_order' => 10,
                'starts_at' => now()->subHour(),
                'ends_at' => null,
            ],
        );
    }
}
