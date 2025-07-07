<?php // این تگ باید اولین خط فایل باشد و هیچ کاراکتر یا خط خالی قبل از آن نباشد.

namespace Database\Seeders; // این خط باید بلافاصله پس از تگ <?php باشد.

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعریف تمام مجوزها به صورت گروه‌بندی شده
        $permissions = [
            // مجوزهای مدیریت پست
            'create post',
            'edit post',
            'delete post',
            'publish post',

            // مجوزهای مشاهده پست
            // 'read post': به معنای خواندن محتوای یک پست خاص است.
            // 'view post': معمولاً برای مشاهده جزئیات یک پست خاص (مثلاً در صفحه نمایش پست) استفاده می‌شود.
            // 'view posts': برای مشاهده لیست یا فهرست پست‌ها (مثلاً در صفحه اصلی وبلاگ یا لیست مدیریت) استفاده می‌شود.
            'read post',
            'view post',
            'view posts',
        ];

        // ایجاد مجوزها با بررسی وجود قبلی و تعیین guard_name
        // guard_name 'web' به معنای استفاده از گارد پیش‌فرض وب لاراول است.
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // ایجاد نقش‌ها با بررسی وجود قبلی و تعیین guard_name
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleEditor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $roleUser = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // اختصاص مجوزها به نقش‌ها با استفاده از syncPermissions()
        // این متد اطمینان می‌دهد که فقط مجوزهای مشخص شده به نقش اختصاص داده شوند و بقیه حذف گردند.
        $roleAdmin->syncPermissions($permissions); // ادمین تمام مجوزها را دارد

        $roleEditor->syncPermissions([
            'create post',
            'edit post',
            'read post',
            'view posts',
            'view post',
        ]);

        $roleUser->syncPermissions([
            'read post',
            'view posts',
            'view post',
        ]);

        // مثال: اختصاص یک نقش به یک کاربر
        // این قسمت را می‌توانید در همین Seeder یا در یک Seeder جداگانه برای کاربران انجام دهید.
        // برای اجرای این بخش، مطمئن شوید که کاربرانی با ID های 1 و 2 در دیتابیس وجود دارند.
        // $user1 = App\Models\User::find(1);
        // if ($user1) {
        //     $user1->assignRole('admin');
        // }

        // $user2 = App\Models\User::find(2);
        // if ($user2) {
        //     $user2->assignRole('editor');
        // }
    }
}