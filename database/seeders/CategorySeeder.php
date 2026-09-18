<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** @var array<int, array{name_bn: string, slug: string}> */
    private array $categories = [
        ['name_bn' => 'বাংলাদেশ', 'slug' => 'national'],
        ['name_bn' => 'রাজনীতি', 'slug' => 'politics'],
        ['name_bn' => 'আন্তর্জাতিক', 'slug' => 'international'],
        ['name_bn' => 'ফিচার', 'slug' => 'feature'],
        ['name_bn' => 'সাক্ষাৎকার', 'slug' => 'interview'],
        ['name_bn' => 'অর্থনীতি', 'slug' => 'economy'],
        ['name_bn' => 'খেলা', 'slug' => 'sports'],
        ['name_bn' => 'বিনোদন', 'slug' => 'entertainment'],
        ['name_bn' => 'প্রযুক্তি', 'slug' => 'technology'],
        ['name_bn' => 'স্বাস্থ্য', 'slug' => 'health'],
        ['name_bn' => 'শিক্ষা', 'slug' => 'education'],
        ['name_bn' => 'জীবনযাপন', 'slug' => 'lifestyle'],
        ['name_bn' => 'মতামত', 'slug' => 'opinion'],
    ];

    public function run(): void
    {
        foreach ($this->categories as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name_bn' => $category['name_bn'],
                    'status' => CategoryStatus::Active,
                    'sort_order' => ($index + 1) * 10,
                    'show_in_menu' => true,
                ],
            );
        }
    }
}
