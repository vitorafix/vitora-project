<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category; // اطمینان حاصل کنید که مدل Category ایمپورت شده باشد

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد چند دسته‌بندی نمونه
        Category::create([
            'name' => 'چای سیاه',
            'slug' => 'black-tea',
            'description' => 'انواع چای سیاه ایرانی و خارجی با عطر و طعم بی‌نظیر.',
        ]);

        Category::create([
            'name' => 'چای سبز',
            'slug' => 'green-tea',
            'description' => 'چای سبز خالص با خواص آنتی‌اکسیدانی و فواید سلامتی.',
        ]);

        Category::create([
            'name' => 'چای میوه‌ای',
            'slug' => 'fruit-tea',
            'description' => 'دمنوش‌ها و چای‌های میوه‌ای با طعم‌های متنوع و دلنشین.',
        ]);

        Category::create([
            'name' => 'دمنوش گیاهی',
            'slug' => 'herbal-infusion',
            'description' => 'انواع دمنوش‌های گیاهی آرامش‌بخش و درمانی.',
        ]);
    }
}
