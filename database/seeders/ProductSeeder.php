<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product; // اطمینان حاصل کنید که مدل Product ایمپورت شده باشد
use App\Models\Category; // اطمینان حاصل کنید که مدل Category ایمپورت شده باشد
use Illuminate\Support\Str; // برای استفاده از Str::slug

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // اطمینان حاصل کنید که دسته‌بندی‌ها قبلاً ایجاد شده‌اند
        $blackTea = Category::where('slug', 'black-tea')->first();
        $greenTea = Category::where('slug', 'green-tea')->first();
        $fruitTea = Category::where('slug', 'fruit-tea')->first();
        $herbalInfusion = Category::where('slug', 'herbal-infusion')->first();

        // اگر دسته‌بندی‌ها موجود نیستند، (نباید این اتفاق بیفتد اگر CategorySeeder اجرا شود)، می‌توانیم ایجادشان کنیم
        // این بخش صرفاً برای اطمینان بیشتر است.
        if (!$blackTea) {
            $blackTea = Category::create(['name' => 'چای سیاه', 'slug' => 'black-tea', 'description' => '...']);
        }
        if (!$greenTea) {
            $greenTea = Category::create(['name' => 'چای سبز', 'slug' => 'green-tea', 'description' => '...']);
        }
        if (!$fruitTea) {
            $fruitTea = Category::create(['name' => 'چای میوه‌ای', 'slug' => 'fruit-tea', 'description' => '...']);
        }
        if (!$herbalInfusion) {
            $herbalInfusion = Category::create(['name' => 'دمنوش گیاهی', 'slug' => 'herbal-infusion', 'description' => '...']);
        }


        // ایجاد چند محصول نمونه
        Product::create([
            'title' => 'چای سیاه ممتاز لاهیجان',
            'description' => 'چای سیاه سنتی با عطر و طعم قوی، محصول بهترین باغات لاهیجان.',
            'price' => 125000,
            'stock' => 100,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Black+Tea+1', // تصویر نمونه
            'category_id' => $blackTea->id,
        ]);

        Product::create([
            'title' => 'چای سبز ژاپنی ماچا',
            'description' => 'پودر چای سبز ماچا، سرشار از آنتی‌اکسیدان، مناسب برای نوشیدنی و آشپزی.',
            'price' => 280000,
            'stock' => 50,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Green+Tea+1',
            'category_id' => $greenTea->id,
        ]);

        Product::create([
            'title' => 'دمنوش بهارنارنج آرامش‌بخش',
            'description' => 'دمنوشی خوش عطر از گل بهارنارنج، مناسب برای رفع استرس و بهبود خواب.',
            'price' => 85000,
            'stock' => 75,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Herbal+Infusion+1',
            'category_id' => $herbalInfusion->id,
        ]);

        Product::create([
            'title' => 'چای ترش (Hibiscus) ارگانیک',
            'description' => 'چای ترش با رنگ زیبا و طعم خاص، مناسب برای کاهش فشار خون.',
            'price' => 95000,
            'stock' => 90,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Herbal+Infusion+2',
            'category_id' => $herbalInfusion->id,
        ]);

        Product::create([
            'title' => 'چای میوه‌ای توت فرنگی و تمشک',
            'description' => 'ترکیبی دلپذیر از چای و میوه‌های جنگلی، مناسب برای لذت بردن در هر فصلی.',
            'price' => 110000,
            'stock' => 60,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Fruit+Tea+1',
            'category_id' => $fruitTea->id,
        ]);

        Product::create([
            'title' => 'چای سیاه سیلان اعلا',
            'description' => 'چای سیلان با کیفیت بالا و طعمی کلاسیک، مناسب برای مصرف روزانه.',
            'price' => 140000,
            'stock' => 80,
            'image' => 'https://placehold.co/400x400/F3F4F6/6B7280?text=Black+Tea+2',
            'category_id' => $blackTea->id,
        ]);
    }
}
