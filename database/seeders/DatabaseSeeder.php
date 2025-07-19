<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ایجاد یک کاربر ادمین با پروفایل کامل و شماره موبایل
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'mobile_number' => '09123456789',
            'national_id' => '1234567890',
            'birth_date' => '1990-01-01',
            'fixed_phone' => '02112345678',
            'profile_completed' => true,
        ]);

        // ایجاد 10 کاربر معمولی
        User::factory(10)->create();

        // اجرای سایر Seederها به ترتیب
        $this->call([
            CategorySeeder::class, // ابتدا دسته‌بندی‌ها باید بارگذاری شوند
            ProductSeeder::class,  // سپس محصولات (که به دسته‌ها نیاز دارند)
            // در صورت وجود Seederهای دیگر، اینجا اضافه کنید
        ]);
    }
}
