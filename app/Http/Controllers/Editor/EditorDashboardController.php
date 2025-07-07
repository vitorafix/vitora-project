<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // برای دسترسی به کاربر لاگین شده

class EditorDashboardController extends Controller
{
    /**
     * نمایش داشبورد اصلی ویرایشگر.
     */
    public function index()
    {
        // می‌توانید داده‌های مربوط به داشبورد (مثلاً تعداد پست‌های پیش‌نویس، دیدگاه‌های در انتظار تایید) را اینجا لود کنید.
        // مثال:
        // $draftPostsCount = Auth::user()->posts()->where('status', 'draft')->count();
        // $pendingCommentsCount = Comment::where('status', 'pending')->count();

        return view('editor.dashboard', [
            // 'draftPostsCount' => $draftPostsCount,
            // 'pendingCommentsCount' => $pendingCommentsCount,
        ]);
    }
}
