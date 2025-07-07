<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Post; // اگر مدل Post را دارید، آن را اینجا ایمپورت کنید
// use Illuminate\Support\Str; // برای استفاده از Str::slug در صورت نیاز

class PostController extends Controller
{
    /**
     * نمایش لیستی از پست‌ها.
     * این متد برای نمایش صفحه 'resources/views/editor/posts/index.blade.php' استفاده می‌شود.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // در اینجا باید پست‌های واقعی را از دیتابیس لود کنید.
        // مثال: $posts = Post::latest()->paginate(10);
        $posts = [
            ['title' => 'چگونه چای سبز دم کنیم؟', 'category' => 'نوشیدنی‌ها', 'status' => 'منتشر شده', 'slug' => 'how-to-brew-green-tea'],
            ['title' => 'تاریخچه چای در ایران', 'category' => 'تاریخچه', 'status' => 'پیش‌نویس', 'slug' => 'history-of-tea-in-iran'],
            ['title' => 'فواید چای سیاه برای سلامتی', 'category' => 'سلامتی', 'status' => 'منتشر شده', 'slug' => 'benefits-of-black-tea'],
        ]; // داده‌های نمونه

        return view('editor.posts.index', compact('posts'));
    }

    /**
     * نمایش فرم برای ایجاد یک پست جدید.
     * این متد برای نمایش صفحه 'resources/views/editor/posts/create.blade.php' استفاده می‌شود.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // می‌توانید دسته‌بندی‌ها یا هر داده دیگری که برای فرم ایجاد پست لازم است را اینجا لود کنید.
        return view('editor.posts.create');
    }

    /**
     * ذخیره یک پست تازه ایجاد شده در دیتابیس.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // اعتبار سنجی داده‌های ورودی
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // منطق ذخیره پست در دیتابیس
        // مثال:
        // $post = Post::create([
        //     'title' => $request->title,
        //     'slug' => Str::slug($request->title), // ایجاد slug از عنوان
        //     'category' => $request->category,
        //     'content' => $request->content,
        //     'user_id' => auth()->id(), // اختصاص پست به کاربر لاگین شده
        //     'status' => 'draft', // وضعیت اولیه
        // ]);

        // ارسال پیام موفقیت و ریدایرکت به لیست پست‌ها
        return redirect()->route('editor.posts.index')->with('success', 'پست جدید با موفقیت ایجاد شد.');
    }

    /**
     * نمایش یک پست خاص.
     * (معمولاً در پنل ادمین/ویرایشگر نیازی به این متد نیست، اما به عنوان بخشی از Resource Controller وجود دارد)
     *
     * @param  string  $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $slug)
    {
        // در اینجا باید پست را از دیتابیس بر اساس slug پیدا کنید.
        // مثال: $post = Post::where('slug', $slug)->firstOrFail();
        // return view('editor.posts.show', compact('post'));
        // یا می‌توانید به صفحه ویرایش ریدایرکت کنید اگر show در پنل ادمین کاربردی نیست.
        return redirect()->route('editor.posts.edit', ['post' => $slug]);
    }

    /**
     * نمایش فرم برای ویرایش یک پست موجود.
     * این متد برای نمایش صفحه 'resources/views/editor/posts/edit.blade.php' استفاده می‌شود.
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function edit(string $slug)
    {
        // در اینجا باید پست را از دیتابیس بر اساس slug پیدا کنید.
        // مثال: $post = Post::where('slug', $slug)->firstOrFail();
        $post = (object)[ // داده نمونه
            'title' => 'چگونه چای سبز دم کنیم؟',
            'category' => 'نوشیدنی‌ها',
            'content' => 'محتوای کامل پست درباره چای سبز...',
            'status' => 'published',
            'slug' => $slug,
        ];

        return view('editor.posts.edit', compact('post'));
    }

    /**
     * به‌روزرسانی یک پست خاص در دیتابیس.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $slug)
    {
        // اعتبار سنجی داده‌های ورودی
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
        ]);

        // منطق پیدا کردن و به‌روزرسانی پست در دیتابیس
        // مثال:
        // $post = Post::where('slug', $slug)->firstOrFail();
        // $post->update([
        //     'title' => $request->title,
        //     'category' => $request->category,
        //     'content' => $request->content,
        //     'status' => $request->status,
        // ]);

        // ارسال پیام موفقیت و ریدایرکت به لیست پست‌ها
        return redirect()->route('editor.posts.index')->with('success', 'پست با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف یک پست خاص از دیتابیس.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $slug)
    {
        // منطق پیدا کردن و حذف پست از دیتابیس
        // مثال:
        // $post = Post::where('slug', $slug)->firstOrFail();
        // $post->delete();

        // ارسال پیام موفقیت و ریدایرکت به لیست پست‌ها
        return redirect()->route('editor.posts.index')->with('success', 'پست با موفقیت حذف شد.');
    }
}
