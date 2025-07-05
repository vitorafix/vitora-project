<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        DB::table('products')->insert([
            [
                'title' => 'چای سیاه ممتاز لاهیجان',
                'description' => 'چای دستچین لاهیجان با عطر طبیعی و طعم ملایم.',
                'price' => 180000,
                'stock' => 50,
                'category_id' => 1, // چای سیاه
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای سبز ارگانیک شمال',
                'description' => 'چای سبز با برگ‌های تازه و بدون سموم شیمیایی.',
                'price' => 200000,
                'stock' => 30,
                'category_id' => 2, // چای سبز
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای ماسالا هندی',
                'description' => 'ترکیب ادویه‌های گرم هندی با چای سیاه.',
                'price' => 250000,
                'stock' => 20,
                'category_id' => 5, // چای ماسالا
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای سفید نپالی',
                'description' => 'چای سفید کمیاب با طعم ملایم و عطر خاص.',
                'price' => 300000,
                'stock' => 10,
                'category_id' => 3, // چای سفید
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای اولانگ تایوانی',
                'description' => 'چای نیمه‌تخمیر شده با طعم میوه‌ای و گلی.',
                'price' => 280000,
                'stock' => 15,
                'category_id' => 4, // چای اولانگ
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای کله مورچه‌ای سنتی',
                'description' => 'چای سیاه پررنگ و پرعطر مناسب مصرف روزانه.',
                'price' => 150000,
                'stock' => 60,
                'category_id' => 1, // چای سیاه
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'دمنوش به لیمو',
                'description' => 'دمنوش گیاهی با طعم خوش به و لیمو، بدون کافئین.',
                'price' => 90000,
                'stock' => 40,
                'category_id' => 6, // چای گیاهی
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'چای سبز ژاپنی',
                'description' => 'چای سبز با کیفیت بالا از ژاپن، با طعم تازه و گیاهی.',
                'price' => 220000,
                'stock' => 25,
                'category_id' => 2, // چای سبز
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
