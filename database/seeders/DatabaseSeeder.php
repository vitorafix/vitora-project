<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // ایمپورت کردن مدل User

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ایجاد یک کاربر ادمین با پروفایل کامل و شماره موبایل به جای رمز عبور
        // این بخش دیگر شامل فیلد 'password' نیست.
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'mobile_number' => '09123456789', // شماره موبایل دلخواه برای ادمین
            'national_id' => '1234567890', // کد ملی دلخواه
            'birth_date' => '1990-01-01',
            'phone' => '02112345678',
            'profile_completed' => true, // تنظیم پروفایل به عنوان تکمیل شده
        ]);

        // ایجاد 10 کاربر معمولی با پروفایل تصادفی (ممکن است کامل یا ناقص باشد)
        // این فراخوانی از UserFactory استفاده می‌کند که قبلاً برای حذف 'password' به‌روزرسانی شده است.
        User::factory(10)->create();

        // فراخوانی Seederهای دیگر
        $this->call([
            // ProductSeeder::class, // مطمئن شوید این Seeder وجود دارد و به درستی کار می‌کند.
            // اگر Seederهای دیگری دارید که کاربران را ایجاد می‌کنند، باید آن‌ها را نیز بررسی کنید.
            // CategorySeeder::class, // مثال: اگر این Seeder را دارید، آن را فعال کنید.
            // OrderSeeder::class, // مثال: اگر این Seeder را دارید، آن را فعال کنید.
        ]);
    }
}
