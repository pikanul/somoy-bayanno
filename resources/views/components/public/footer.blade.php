<footer class="mt-10 bg-brand-dark text-white">
    <div class="public-container grid gap-8 py-10 md:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            <p class="text-2xl font-bold text-white">দৈনিক সময় বায়ান্ন</p>
            <p class="mt-1 text-sm font-semibold uppercase tracking-normal text-white/70">The Daily Somoy Bayanno</p>
            <p class="mt-4 max-w-md text-sm leading-6 text-white/70">বিশ্বস্ত সংবাদ, বিশ্লেষণ ও জনস্বার্থের তথ্য পাঠকের কাছে পৌঁছে দেওয়ার অঙ্গীকার।</p>
        </div>
        <nav aria-label="ফুটার নেভিগেশন">
            <h2 class="text-sm font-bold">বিভাগ</h2>
            <ul class="mt-3 space-y-2 text-sm text-white/75">
                <li><a class="hover:text-white" href="#">জাতীয়</a></li>
                <li><a class="hover:text-white" href="#">রাজনীতি</a></li>
                <li><a class="hover:text-white" href="#">অর্থনীতি</a></li>
                <li><a class="hover:text-white" href="#">খেলা</a></li>
            </ul>
        </nav>
        <div>
            <h2 class="text-sm font-bold">যোগাযোগ</h2>
            <address class="mt-3 not-italic text-sm leading-6 text-white/75">
                ঢাকা, বাংলাদেশ<br>
                news@example.com
            </address>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="public-container py-4 text-sm text-white/60">
            &copy; {{ now()->year }} দৈনিক সময় বায়ান্ন. সর্বস্বত্ব সংরক্ষিত।
        </div>
    </div>
</footer>
