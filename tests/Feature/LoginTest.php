<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function send_otp_with_valid_mobile_and_name()
    {
        // برای جلوگیری از خطاهای middleware تو تست
        $this->withoutMiddleware();

        // ایجاد کاربر نمونه
        $user = User::factory()->create([
            'name' => 'علی',
            'lastname' => 'محمدی',
            'mobile_number' => '09123456789',
        ]);

        // ارسال درخواست ارسال OTP
        $response = $this->post('/auth/send-otp', [
            'name' => 'علی',
            'lastname' => 'محمدی',
            'mobile_number' => '09123456789',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);
    }

    /** @test */
    public function verify_otp_and_login_user()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create([
            'name' => 'علی',
            'lastname' => 'محمدی',
            'mobile_number' => '09123456789',
        ]);

        // فرض کنیم OTP تستی 123456 همیشه معتبر است
        $response = $this->post('/auth/verify-otp', [
            'mobile_number' => '09123456789',
            'otp' => '123456',
        ]);

        // بعد از لاگین، معمولا ریدایرکت به داشبورد میشه
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        // بررسی اینکه کاربر احراز هویت شده است
        $this->assertAuthenticatedAs($user);
    }
}
