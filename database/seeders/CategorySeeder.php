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
                'id' => 3,
                'name' => 'چای سیاه',
                'slug' => 'chai-siah',
                'description' => 'چای سیاه با طعم قوی و رنگ تیره، شامل چای لاهیجان و کله مورچه‌ای.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // سایر دسته‌ها...
        ]);
    }
}
