<x-public.layout
    :title="($filters['title'] ?? 'সংবাদ আর্কাইভ').' - '.config('app.name')"
    description="দৈনিক সময় বায়ান্ন প্রকাশিত সংবাদ আর্কাইভ"
    :canonical="url()->current()"
>
    <section class="bg-white p-4 sm:p-6" aria-labelledby="archive-heading">
        <div class="flex flex-col gap-2 border-b border-neutral-200 pb-5">
            <p class="text-sm font-bold text-brand-red">Archive</p>
            <h1 id="archive-heading" class="text-3xl font-bold text-brand-dark">{{ $filters['title'] ?? 'সংবাদ আর্কাইভ' }}</h1>
            @if (! empty($filters['label']))
                <p class="text-sm text-neutral-600">{{ $filters['label'] }}</p>
            @endif
        </div>

        <form action="{{ url()->current() }}" method="get" class="mt-6 grid gap-4 md:grid-cols-[1fr_1fr_auto]">
            <div>
                <label for="archive-category" class="mb-1 block text-sm font-semibold text-neutral-700">বিভাগ</label>
                <select id="archive-category" name="category" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
                    <option value="">সব বিভাগ</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name_bn }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="archive-author" class="mb-1 block text-sm font-semibold text-neutral-700">লেখক</label>
                <select id="archive-author" name="author" class="min-h-11 w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-brand-green">
                    <option value="">সব লেখক</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}" @selected((string) ($filters['author'] ?? '') === (string) $author->id)>{{ $author->name_bn }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="min-h-11 bg-brand-green px-5 py-2 text-sm font-bold text-white hover:bg-green-800">ফিল্টার</button>
                <a href="{{ url()->current() }}" class="inline-flex min-h-11 items-center px-4 py-2 text-sm font-semibold text-neutral-700 hover:text-brand-green">রিসেট</a>
            </div>
        </form>
    </section>

    <section class="mt-6" aria-labelledby="archive-results-heading">
        <div class="mb-4">
            <h2 id="archive-results-heading" class="text-2xl font-bold text-brand-dark">প্রকাশিত সংবাদ</h2>
            <p class="mt-1 text-sm text-neutral-600">{{ $articles->total() }}টি সংবাদ পাওয়া গেছে</p>
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
                <p class="text-lg font-bold text-brand-dark">এই আর্কাইভে কোনো সংবাদ নেই</p>
                <p class="mt-2 text-sm text-neutral-600">অন্য সময় বা ফিল্টার বেছে দেখুন।</p>
            </div>
        @endif
    </section>
</x-public.layout>
