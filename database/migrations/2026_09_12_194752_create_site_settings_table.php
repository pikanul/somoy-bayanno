<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            [
                'key' => 'footer_publication_info',
                'label' => 'Footer publication information',
                'value' => 'সম্পাদক ও প্রকাশক : শম্ভু চন্দ্র সরকার : প্রধান কার্যালয় : হোল্ডিং ৯৪, লেন ৪, ব্লক-এ, রোড ৬, বাইপাইল আশুলিয়া, সাভার ঢাকা-১৩৪৯ থেকে প্রকাশক কর্তৃক প্রকাশিত বিসমিল্লাহ প্রিন্টিং প্রেস, ১২৯ ফকিরাপুল, (১ম লেন নীচতলা), মতিঝিল ঢাকা-১০০০ থেকে মুদ্রিত। যোগাযোগ: ০৯৬১১৬৭৯৫২০, মোবাইল: ০১৫৫৪৭৩৩৩২২ : E-mail: dailysomoybayanno@gmail.com, বার্তা : somoybayanno@gmail.com বিজ্ঞাপন: ০১৮৭০-৭০১৫২০',
                'type' => 'textarea',
                'is_public' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_show_publication_info',
                'label' => 'Show footer publication information',
                'value' => '1',
                'type' => 'boolean',
                'is_public' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_correspondent_label',
                'label' => 'Footer correspondent list label',
                'value' => 'প্রতিনিধি তালিকা',
                'type' => 'text',
                'is_public' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_story_tagline',
                'label' => 'Footer story tagline',
                'value' => 'বাংলাদেশের গল্প, বিশ্ববাসীর কাছে',
                'type' => 'text',
                'is_public' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_copyright',
                'label' => 'Footer copyright text',
                'value' => '© ২০২৬ দৈনিক সময় বায়ান্ন. সর্বস্বত্ব সংরক্ষিত।',
                'type' => 'text',
                'is_public' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
