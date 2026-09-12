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
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'group')) {
                $table->string('group')->default('general')->after('value');
            }
        });

        DB::table('site_settings')
            ->whereIn('key', [
                'footer_publication_info',
                'footer_show_publication_info',
                'footer_correspondent_label',
                'footer_story_tagline',
                'footer_copyright',
            ])
            ->update(['group' => 'footer']);

        $now = now();

        DB::table('site_settings')->upsert([
            ['key' => 'footer_logo_path', 'label' => 'Footer logo path', 'value' => 'demo-home/logo.png', 'group' => 'footer', 'type' => 'text', 'is_public' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_tagline', 'label' => 'Footer tagline', 'value' => 'সময়ের সংবাদ, সত্যের সঙ্গে', 'group' => 'footer', 'type' => 'text', 'is_public' => true, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_description', 'label' => 'Footer description', 'value' => 'দেশ, সমাজ ও মানুষের গল্প নিয়ে সবসময় আপনার পাশে। নির্ভরযোগ্য, বস্তুনিষ্ঠ ও দায়িত্বশীল সংবাদ পরিবেশন আমাদের অঙ্গীকার।', 'group' => 'footer', 'type' => 'textarea', 'is_public' => true, 'sort_order' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_about_button_label', 'label' => 'Footer about button label', 'value' => 'আমাদের সম্পর্কে জানুন', 'group' => 'footer', 'type' => 'text', 'is_public' => true, 'sort_order' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_facebook_url', 'label' => 'Facebook URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_x_url', 'label' => 'X / Twitter URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 110, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_youtube_url', 'label' => 'YouTube URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_instagram_url', 'label' => 'Instagram URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 130, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_linkedin_url', 'label' => 'LinkedIn URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 140, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_social_whatsapp_url', 'label' => 'WhatsApp URL', 'value' => '', 'group' => 'footer_social', 'type' => 'url', 'is_public' => true, 'sort_order' => 150, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_newsletter_enabled', 'label' => 'Enable footer newsletter block', 'value' => '1', 'group' => 'footer_newsletter', 'type' => 'boolean', 'is_public' => true, 'sort_order' => 160, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_newsletter_heading', 'label' => 'Newsletter heading', 'value' => 'নিউজলেটার সাবস্ক্রাইব করুন', 'group' => 'footer_newsletter', 'type' => 'text', 'is_public' => true, 'sort_order' => 170, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_newsletter_description', 'label' => 'Newsletter description', 'value' => 'সর্বশেষ সংবাদ ও বিশেষ প্রতিবেদন সরাসরি আপনার ইমেইলে পেতে।', 'group' => 'footer_newsletter', 'type' => 'textarea', 'is_public' => true, 'sort_order' => 180, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_mobile_apps_enabled', 'label' => 'Enable mobile app block', 'value' => '1', 'group' => 'footer_apps', 'type' => 'boolean', 'is_public' => true, 'sort_order' => 190, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_google_play_url', 'label' => 'Google Play URL', 'value' => '', 'group' => 'footer_apps', 'type' => 'url', 'is_public' => true, 'sort_order' => 200, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_app_store_url', 'label' => 'App Store URL', 'value' => '', 'group' => 'footer_apps', 'type' => 'url', 'is_public' => true, 'sort_order' => 210, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_privacy_url', 'label' => 'Privacy policy URL', 'value' => '/pages/privacy-policy', 'group' => 'footer_legal', 'type' => 'text', 'is_public' => true, 'sort_order' => 220, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_terms_url', 'label' => 'Terms URL', 'value' => '/pages/terms', 'group' => 'footer_legal', 'type' => 'text', 'is_public' => true, 'sort_order' => 230, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_sitemap_url', 'label' => 'Sitemap URL', 'value' => '/sitemap.xml', 'group' => 'footer_legal', 'type' => 'text', 'is_public' => true, 'sort_order' => 240, 'created_at' => $now, 'updated_at' => $now],
        ], ['key'], ['label', 'value', 'group', 'type', 'is_public', 'sort_order', 'updated_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'group')) {
                $table->dropColumn('group');
            }
        });
    }
};
