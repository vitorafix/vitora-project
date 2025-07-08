<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // حذف داده‌های قبلی به صورت اصولی (بدون truncate)
        DB::table('categories')->delete();

        // درج داده‌های جدید
        DB::table('categories')->insert([
            [
                'id' => 1,
                'name' => 'چای سیاه',
                'slug' => 'chai-siah',
                'description' => 'چای سیاه با طعم قوی و رنگ تیره.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'چای سبز',
                'slug' => 'chai-sabz',
                'description' => 'چای سبز تازه و خوش طعم.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // دسته‌های بیشتر در صورت نیاز...
        ]);
    }
}
