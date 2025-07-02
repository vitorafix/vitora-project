<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            [
                'name' => 'چای سیاه',
                'slug' => 'chai-siah',
                'description' => 'چای سیاه با طعم قوی و رنگ تیره، شامل چای لاهیجان و کله مورچه‌ای.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'چای سبز',
                'slug' => 'chai-sabz',
                'description' => 'چای سبز با برگ‌های تازه و طعمی ملایم.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'چای سفید',
                'slug' => 'chai-sefid',
                'description' => 'چای سفید کم‌فرآوری شده، با طعمی بسیار ملایم و لطیف.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'چای اولانگ',
                'slug' => 'chai-oolong',
                'description' => 'چای نیمه‌تخمیر شده با طعمی بین چای سبز و سیاه.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'چای ماسالا',
                'slug' => 'chai-masala',
                'description' => 'چای سیاه ترکیب شده با ادویه‌های گرم مثل دارچین و زنجبیل.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'چای گیاهی',
                'slug' => 'chai-giahi',
                'description' => 'دمنوش‌ها و چای‌های بدون کافئین شامل گیاهان دارویی مختلف.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
