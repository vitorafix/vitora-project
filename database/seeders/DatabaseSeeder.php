<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Hash; // برای رمزنگاری رمز عبور
use App\Models\User; // برای ایجاد کاربر ادمین

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * پایگاه داده را با داده‌های اولیه پر می‌کند.
     */
    public function run(): void
    {
        // 1. ایجاد یک کاربر ادمین (Admin User)
        // این کاربر برای دسترسی به پنل ادمین (در فاز بعدی) مفید خواهد بود.
        User::firstOrCreate(
            ['email' => 'admin@example.com'], // ایمیل را به عنوان معیار جستجو قرار دهید
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // رمز عبور: password
                'email_verified_at' => now(),
                // می‌توانید فیلدهای mobile_number, address و غیره را اینجا نیز پر کنید.
                'profile_completed' => true, // فرض می‌کنیم ادمین پروفایلش کامل است
            ]
        );

        // 2. ایجاد دسته‌بندی‌ها (Categories)
        $blackTea = Category::firstOrCreate(['name' => 'چای سیاه'], [
            'slug' => 'black-tea',
            'description' => 'انواع چای سیاه ایرانی و خارجی با طعم‌های بی‌نظیر.'
        ]);
        $greenTea = Category::firstOrCreate(['name' => 'چای سبز'], [
            'slug' => 'green-tea',
            'description' => 'چای سبز خالص و ارگانیک با خواص سلامتی فراوان.'
        ]);
        $herbalTea = Category::firstOrCreate(['name' => 'دمنوش‌ها'], [
            'slug' => 'herbal-teas',
            'description' => 'دمنوش‌های گیاهی متنوع برای آرامش و سلامتی.'
        ]);
        $specialTea = Category::firstOrCreate(['name' => 'چای‌های ویژه'], [
            'slug' => 'special-teas',
            'description' => 'مجموعه‌ای از چای‌های نادر و خاص برای ذائقه‌های متفاوت.'
        ]);


        // 3. ایجاد محصولات (Products)
        // اطمینان حاصل کنید که هر محصول به یک category_id معتبر مرتبط است.
        // تصویر: می‌توانید از URLهای واقعی استفاده کنید یا از placehold.co
        Product::firstOrCreate(['title' => 'چای سیاه ممتاز لاهیجان'], [
            'description' => 'چای سیاه دست‌چین شده از باغات لاهیجان، با عطر و طعم بی‌نظیر.',
            'price' => 120000,
            'stock' => 50,
            'image' => 'https://placehold.co/600x400/2f855a/ffffff?text=Black+Tea+Lahijan',
            'category_id' => $blackTea->id,
        ]);

        Product::firstOrCreate(['title' => 'چای سبز بهاره', 'category_id' => $greenTea->id], [
            'description' => 'چای سبز بهاره، طراوت و سرزندگی را به شما هدیه می‌دهد.',
            'price' => 95000,
            'stock' => 70,
            'image' => 'https://placehold.co/600x400/38a169/ffffff?text=Green+Tea+Spring',
            'category_id' => $greenTea->id,
        ]);

        Product::firstOrCreate(['title' => 'دمنوش آرامش بخش', 'category_id' => $herbalTea->id], [
            'description' => 'ترکیبی از گیاهان دارویی برای آرامش اعصاب و خواب راحت.',
            'price' => 75000,
            'stock' => 30,
            'image' => 'https://placehold.co/600x400/8B5CF6/ffffff?text=Herbal+Tea+Relax',
            'category_id' => $herbalTea->id,
        ]);

        Product::firstOrCreate(['title' => 'چای اولانگ', 'category_id' => $specialTea->id], [
            'description' => 'چای نیمه تخمیری با طعم خاص و فواید بی‌شمار.',
            'price' => 180000,
            'stock' => 25,
            'image' => 'https://placehold.co/600x400/6D28D9/ffffff?text=Oolong+Tea',
            'category_id' => $specialTea->id,
        ]);

        Product::firstOrCreate(['title' => 'چای سبز مراکشی', 'category_id' => $greenTea->id], [
            'description' => 'چای سبز با برگ‌های نعناع، مناسب برای پذیرایی و انرژی‌بخشی.',
            'price' => 110000,
            'stock' => 40,
            'image' => 'https://placehold.co/600x400/059669/ffffff?text=Moroccan+Green+Tea',
            'category_id' => $greenTea->id,
        ]);

        Product::firstOrCreate(['title' => 'چای سفید سیلان', 'category_id' => $specialTea->id], [
            'description' => 'کمیاب‌ترین و لطیف‌ترین چای با خواص آنتی‌اکسیدانی بالا.',
            'price' => 250000,
            'stock' => 15,
            'image' => 'https://placehold.co/600x400/9CA3AF/ffffff?text=White+Tea+Ceylon',
            'category_id' => $specialTea->id,
        ]);

        Product::firstOrCreate(['title' => 'دمنوش میوه‌های جنگلی', 'category_id' => $herbalTea->id], [
            'description' => 'ترکیبی خوش‌طعم از میوه‌های قرمز جنگلی با عطر دلنشین.',
            'price' => 85000,
            'stock' => 35,
            'image' => 'https://placehold.co/600x400/F59E0B/ffffff?text=Forest+Fruit+Tea',
            'category_id' => $herbalTea->id,
        ]);
    }
}
