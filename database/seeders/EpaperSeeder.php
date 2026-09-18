<?php

namespace Database\Seeders;

use App\Models\Epaper;
use Illuminate\Database\Seeder;

class EpaperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Epaper::query()->updateOrCreate(
            ['issue_date' => '2026-09-13'],
            [
                'title_bn' => 'দৈনিক সময় বায়ান্ন ই-পেপার',
                'edition' => 'ঢাকা',
                'scan_path' => 'epapers/bayanno-sample-2026-09-13.jpg',
                'pdf_path' => null,
                'external_url' => 'https://drive.google.com/file/d/1sS868852inaWYML8VzkWsAJLKEYoA_9N/view?usp=sharing',
                'pages' => [
                    [
                        'page_number' => 1,
                        'title' => 'প্রথম পৃষ্ঠা',
                        'scan_path' => 'epapers/bayanno-sample-2026-09-13.jpg',
                        'pdf_path' => null,
                        'external_url' => 'https://drive.google.com/file/d/1sS868852inaWYML8VzkWsAJLKEYoA_9N/view?usp=sharing',
                    ],
                ],
                'is_published' => true,
                'sort_order' => 10,
            ],
        );
    }
}
