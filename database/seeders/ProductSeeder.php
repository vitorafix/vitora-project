<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // حذف داده‌های قبلی محصولات
        DB::table('products')->delete();

        // درج محصولات جدید
        DB::table('products')->insert([
            [
                'title' => 'چای سیاه ممتاز لاهیجان',
                'description' => 'چای دستچین لاهیجان با عطر طبیعی و طعم ملایم.',
                'price' => 180000,
                'stock' => 50,
                'category_id' => 1, // دسته چای سیاه
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای کله مورچه‌ای سنتی',
                'description' => 'چای سیاه پررنگ و پرعطر مناسب مصرف روزانه.',
                'price' => 150000,
                'stock' => 60,
                'category_id' => 1,
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای شکسته مرغوب',
                'description' => 'چای سیاه با کیفیت بالا و طعم اصیل ایرانی.',
                'price' => 140000,
                'stock' => 40,
                'category_id' => 1,
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای باروتی لاهیجان',
                'description' => 'چای سیاه قوی با طعمی خاص و ماندگار.',
                'price' => 160000,
                'stock' => 35,
                'category_id' => 1,
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای بهاره',
                'description' => 'چای سیاه تازه برداشت شده در فصل بهار.',
                'price' => 170000,
                'stock' => 45,
                'category_id' => 1,
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای کوهستانی',
                'description' => 'چای سیاه مرغوب برداشت شده از ارتفاعات شمال.',
                'price' => 190000,
                'stock' => 25,
                'category_id' => 1,
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
