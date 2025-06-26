    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;

    class BlogController extends Controller
    {
        /**
         * Display a listing of the blog posts.
         *
         * @return \Illuminate\View\View
         */
        public function index()
        {
            // در آینده می‌توانید لیست مقالات واقعی را از دیتابیس اینجا دریافت کنید
            // برای مثال: $posts = BlogPost::all();
            return view('blog'); // فرض می‌کنیم view شما blog.blade.php باشد
        }

        /**
         * Display the specified blog post.
         *
         * @param  int  $id
         * @return \Illuminate\View\View
         */
        public function show($id)
        {
            // در آینده می‌توانید مقاله مورد نظر را از دیتابیس دریافت کنید
            // برای مثال: $post = BlogPost::findOrFail($id);
            return view('blog-single', ['id' => $id]); // فرض می‌کنیم view شما blog-single.blade.php باشد
        }
    }
    