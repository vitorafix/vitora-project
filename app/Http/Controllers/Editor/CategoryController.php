<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Category; // اگر مدل Category را دارید، آن را اینجا ایمپورت کنید
// use Illuminate\Support\Str; // برای استفاده از Str::slug در صورت نیاز

class CategoryController extends Controller
{
    /**
     * نمایش لیستی از دسته‌بندی‌ها.
     * این متد برای نمایش صفحه 'resources/views/editor/categories/index.blade.php' استفاده می‌شود.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // در اینجا باید دسته‌بندی‌های واقعی را از دیتابیس لود کنید.
        // مثال: $categories = Category::latest()->get();
        $categories = [
            ['name' => 'نوشیدنی‌ها', 'description' => 'انواع چای و دمنوش‌ها', 'id' => 1],
            ['name' => 'تاریخچه', 'description' => 'مقالات مربوط به تاریخچه چای', 'id' => 2],
            ['name' => 'سلامتی', 'description' => 'فواید چای برای سلامتی', 'id' => 3],
        ]; // داده‌های نمونه

        return view('editor.categories.index', compact('categories'));
    }

    /**
     * نمایش فرم برای ایجاد یک دسته‌بندی جدید.
     * (اگر فرم ایجاد در یک مدال یا صفحه جداگانه باشد)
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // اگر فرم ایجاد دسته‌بندی در یک صفحه جداگانه است، این متد آن ویو را بازمی‌گرداند.
        // return view('editor.categories.create');
        abort(404); // اگر از مدال برای افزودن استفاده می‌کنید
    }

    /**
     * ذخیره یک دسته‌بندی تازه ایجاد شده در دیتابیس.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // اعتبار سنجی داده‌های ورودی
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        // منطق ذخیره دسته‌بندی در دیتابیس
        // مثال:
        // Category::create([
        //     'name' => $request->name,
        //     'slug' => Str::slug($request->name),
        //     'description' => $request->description,
        // ]);

        return redirect()->route('editor.categories.index')->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
    }

    /**
     * نمایش یک دسته‌بندی خاص.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        // نیازی به پیاده‌سازی این متد نیست مگر اینکه بخواهید صفحه جزئیات دسته‌بندی را داشته باشید.
        abort(404);
    }

    /**
     * نمایش فرم برای ویرایش یک دسته‌بندی موجود.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        // در اینجا باید دسته‌بندی را از دیتابیس بر اساس ID پیدا کنید.
        // مثال: $category = Category::findOrFail($id);
        // return view('editor.categories.edit', compact('category'));
        abort(404); // اگر از مدال برای ویرایش استفاده می‌کنید
    }

    /**
     * به‌روزرسانی یک دسته‌بندی خاص در دیتابیس.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, int $id)
    {
        // اعتبار سنجی داده‌های ورودی
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:1000',
        ]);

        // منطق پیدا کردن و به‌روزرسانی دسته‌بندی در دیتابیس
        // مثال:
        // $category = Category::findOrFail($id);
        // $category->update([
        //     'name' => $request->name,
        //     'description' => $request->description,
        // ]);

        return redirect()->route('editor.categories.index')->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف یک دسته‌بندی خاص از دیتابیس.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        // منطق پیدا کردن و حذف دسته‌بندی از دیتابیس
        // مثال:
        // $category = Category::findOrFail($id);
        // $category->delete();

        return redirect()->route('editor.categories.index')->with('success', 'دسته‌بندی با موفقیت حذف شد.');
    }
}
