<?php

namespace Database\Seeders;

use App\Enums\AuthorStatus;
use App\Models\Author;
use Illuminate\Database\Seeder;

class CorrespondentSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            [
                'slug' => 'md-nurul-amin-helaly',
                'name_bn' => 'মো: নুরুল আমিন হেলালী',
                'name_en' => 'Md Nurul Amin Helaly',
                'designation' => 'নির্বাহী সম্পাদক',
                'address' => "Cox's Bazar",
                'phone' => '+8801818145795',
                'email' => 'aminhelali78@gmail.com',
                'photo' => 'authors/md-nurul-amin-helaly.jpeg',
                'organization_level' => 1,
                'sort_order' => 10,
                'featured' => true,
                'bio_bn' => 'সম্পাদকীয় নেতৃত্ব, প্রকাশনা পরিকল্পনা ও কক্সবাজারভিত্তিক সংবাদ সমন্বয়ে দায়িত্বপ্রাপ্ত।',
            ],
            [
                'slug' => 'abdur-rahim',
                'name_bn' => 'আব্দুর রহিম',
                'name_en' => 'Abdur Rahim',
                'designation' => 'Multimedia Executive Editor',
                'address' => "Ukhiya, Cox's Bazar",
                'phone' => '01831332244',
                'email' => '4332244rahim@gmail.com',
                'photo' => 'authors/abdur-rahim.jpeg',
                'organization_level' => 2,
                'sort_order' => 20,
                'featured' => true,
                'bio_bn' => 'মাল্টিমিডিয়া ডেস্ক, ডিজিটাল কনটেন্ট, নিউজ এডিটিং ও ভিডিও প্রোডাকশন সমন্বয় করেন।',
            ],
            [
                'slug' => 'mohammed-ayes',
                'name_bn' => 'মোহাম্মদ আয়েস',
                'name_en' => 'Mohammed Ayes',
                'designation' => 'Multimedia Coordinator',
                'address' => "Ramu, Cox's Bazar",
                'phone' => '01613553450',
                'email' => 'mohammeedayes@gmail.com',
                'photo' => 'authors/mohammed-ayes.jpeg',
                'organization_level' => 3,
                'sort_order' => 30,
                'featured' => false,
                'bio_bn' => 'মাল্টিমিডিয়া কনটেন্ট, ফিল্ড আপডেট এবং প্রকাশনা সমন্বয়ে কাজ করেন।',
            ],
            [
                'slug' => 'correspondent-photo-slot-01',
                'name_bn' => 'নাম যুক্ত করুন',
                'name_en' => 'Add Name',
                'designation' => 'পদবি যুক্ত করুন',
                'address' => null,
                'phone' => null,
                'email' => null,
                'photo' => 'authors/correspondent-profile-01.jpeg',
                'organization_level' => 4,
                'sort_order' => 80,
                'featured' => false,
                'bio_bn' => 'এই প্রোফাইলটি admin panel থেকে সম্পাদনা করে নতুন প্রতিনিধি যুক্ত করা যাবে।',
            ],
            [
                'slug' => 'correspondent-photo-slot-04',
                'name_bn' => 'নাম যুক্ত করুন',
                'name_en' => 'Add Name',
                'designation' => 'পদবি যুক্ত করুন',
                'address' => null,
                'phone' => null,
                'email' => null,
                'photo' => 'authors/correspondent-profile-04.jpeg',
                'organization_level' => 4,
                'sort_order' => 90,
                'featured' => false,
                'bio_bn' => 'এই প্রোফাইলটি admin panel থেকে সম্পাদনা করে নতুন প্রতিনিধি যুক্ত করা যাবে।',
            ],
        ];

        foreach ($authors as $author) {
            Author::query()->updateOrCreate(
                ['slug' => $author['slug']],
                [
                    ...$author,
                    'status' => AuthorStatus::Active,
                    'seo_title' => $author['name_bn'],
                    'seo_description' => $author['designation'],
                ],
            );
        }
    }
}
