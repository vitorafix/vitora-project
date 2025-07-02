<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * نمایش لیست مقالات وبلاگ.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        // در اینجا می‌توانید منطق دریافت مقالات واقعی را از دیتابیس اضافه کنید.
        $posts = [
            (object)[
                'id' => 1,
                'title' => 'عنوان مقاله اول',
                'slug' => 'first-post',
                'excerpt' => 'خلاصه‌ای از مقاله اول...',
                'image' => 'https://placehold.co/600x400/E0F2F1/004D40?text=Blog+Post+1',
            ],
            (object)[
                'id' => 2,
                'title' => 'عنوان مقاله دوم',
                'slug' => 'second-post',
                'excerpt' => 'خلاصه‌ای از مقاله دوم...',
                'image' => 'https://placehold.co/600x400/E0F2F1/004D40?text=Blog+Post+2',
            ],
        ];

        return view('blog.index', compact('posts'));
    }

    /**
     * نمایش یک مقاله وبلاگ خاص.
     *
     * @param string $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $id): View|\Illuminate\Http\RedirectResponse
    {
        // در اینجا منطق دریافت مقاله خاص از دیتابیس اضافه کنید.
        $post = (object)[
            'id' => $id,
            'title' => 'عنوان مقاله ' . $id,
            'content' => 'این متن کامل مقاله ' . $id . ' است.',
            'image' => 'https://placehold.co/800x600/E0F2F1/004D40?text=Blog+Post+' . $id,
        ];

        if (!$post) {
            return redirect()->route('blog.index')->with('error', 'مقاله مورد نظر یافت نشد.');
        }

        return view('blog.show', compact('post'));
    }
}
