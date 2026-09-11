<x-public.layout
    :title="'অনুসন্ধান - '.config('app.name')"
    description="দৈনিক সময় বায়ান্ন সংবাদ অনুসন্ধান"
    :canonical="route('search')"
>
    <section class="bg-white p-4 sm:p-6" aria-labelledby="search-heading">
        <div class="flex flex-col gap-2 border-b border-neutral-200 pb-5">
            <p class="text-sm font-bold text-brand-red">Search</p>
            <h1 id="search-heading" class="text-3xl font-bold text-brand-dark">সংবাদ অনুসন্ধান</h1>
        </div>

        <form action="{{ route('search') }}" method="get" class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-[minmax(220px,2fr)_repeat(5,minmax(120px,1fr))]">
            <div>
                <label for="search-q" class="mb-1 block text-sm font-semibold text-neutral-700">কীওয়ার্ড</label>
                <input id="search-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="120" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green" placeholder="শিরোনাম, লেখক, ট্যাগ">
                @error('q')
                    <p class="mt-1 text-sm text-brand-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="search-category" class="mb-1 block text-sm font-semibold text-neutral-700">বিভাগ</label>
                <select id="search-category" name="category" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
                    <option value="">সব</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name_bn }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search-author" class="mb-1 block text-sm font-semibold text-neutral-700">লেখক</label>
                <select id="search-author" name="author" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
                    <option value="">সব</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}" @selected((string) ($filters['author'] ?? '') === (string) $author->id)>{{ $author->name_bn }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search-from" class="mb-1 block text-sm font-semibold text-neutral-700">শুরু</label>
                <input id="search-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
            </div>

            <div>
                <label for="search-to" class="mb-1 block text-sm font-semibold text-neutral-700">শেষ</label>
                <input id="search-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
            </div>

            <div>
                <label for="search-sort" class="mb-1 block text-sm font-semibold text-neutral-700">সাজান</label>
                <select id="search-sort" name="sort" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
                    <option value="relevance" @selected(($filters['sort'] ?? 'relevance') === 'relevance')>প্রাসঙ্গিকতা</option>
                    <option value="latest" @selected(($filters['sort'] ?? 'relevance') === 'latest')>সর্বশেষ</option>
                </select>
            </div>

            <div class="md:col-span-2 lg:col-span-6">
                <button type="submit" class="min-h-11 bg-brand-green px-5 py-2 text-sm font-bold text-white hover:bg-green-800">খুঁজুন</button>
                <a href="{{ route('search') }}" class="ml-3 inline-flex min-h-11 items-center px-4 py-2 text-sm font-semibold text-neutral-700 hover:text-brand-green">রিসেট</a>
            </div>
        </form>
    </section>

    <section class="mt-6" aria-labelledby="search-results-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="search-results-heading" class="text-2xl font-bold text-brand-dark">ফলাফল</h2>
                <p class="mt-1 text-sm text-neutral-600">{{ $articles->total() }}টি সংবাদ পাওয়া গেছে</p>
            </div>
        </div>

        @if ($articles->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-public.article-card
                        :title="$article->headline_bn"
                        :summary="$article->summary_bn"
                        :url="route('articles.show', $article->slug)"
                        :image="$article->featuredMedia?->url() ?? $article->featured_image_url"
                        :category="$article->primaryCategory?->name_bn"
                        :published-at="$article->published_at"
                    />
                @endforeach
            </div>

            <div class="mt-6">
                {{ $articles->links() }}
            </div>
        @else
            <div class="bg-white p-8 text-center">
                <p class="text-lg font-bold text-brand-dark">কোনো সংবাদ পাওয়া যায়নি</p>
                <p class="mt-2 text-sm text-neutral-600">অন্য কীওয়ার্ড বা ফিল্টার দিয়ে আবার চেষ্টা করুন।</p>
            </div>
        @endif
    </section>
</x-public.layout>
