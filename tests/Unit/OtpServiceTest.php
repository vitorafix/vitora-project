<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\Store as SessionStore;
use Mockery;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OtpService $otpService;
    protected $rateLimitServiceMock;
    protected $sessionMock;
    protected $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpService = new OtpService();

        // Mock کردن RateLimitService
        $this->rateLimitServiceMock = Mockery::mock('App\Contracts\Services\RateLimitServiceInterface');
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')->andReturn(true)
            ->shouldReceive('checkAndIncrementSendAttempts')->andReturn(true)
            ->shouldReceive('checkAndIncrementVerifyAttempts')->andReturn(true)
            ->shouldReceive('resetVerifyAttempts')->andReturnNull()
            ->shouldReceive('resetIpAttempts')->andReturnNull();

        // Mock کردن SessionStore
        $this->sessionMock = Mockery::mock(SessionStore::class);
        $this->sessionMock->shouldReceive('put')->andReturnNull();

        // Mock AuditLogger (callable)
        $this->auditLogger = function($message, $level = 'info', $userId = null, $model = null, $modelId = null) {
            // فقط لاگ بزنیم (می‌تونیم Assert کنیم در صورت نیاز)
            Log::info("AuditLog: [$level] $message");
        };

        // فیک Cache و Crypt با دستورات زیر، اما چون Cache و Crypt واقعی‌ هستن در Laravel TestCase
        Cache::flush();
        Crypt::setKey(config('app.key')); // اگر لازم بود

        // برای تولید و تایید OTP، Crypt واقعی است و Cache واقعی با RefreshDatabase
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_and_store_otp_and_verify_success()
    {
        $mobile = '09123456789';

        $otp = $this->otpService->generateAndStoreOtp($mobile);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        $verified = $this->otpService->verifyOtp($mobile, $otp);

        $this->assertTrue($verified);
    }

    public function test_sendOtpForMobile_for_new_user()
    {
        $mobile = '09129998877';
        $ip = '123.123.123.123';

        // مطمئن شو کاربر وجود ندارد
        $this->assertDatabaseMissing('users', ['mobile_number' => $mobile]);

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );

        // مطمئن شو session برای ثبت نام تنظیم شده
        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_registration', Mockery::type('string'))->once();
        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_otp', Mockery::type('string'))->once();
    }

    public function test_sendOtpForMobile_for_existing_user()
    {
        $mobile = '09120001122';
        $ip = '111.111.111.111';

        // ایجاد کاربر
        User::factory()->create(['mobile_number' => $mobile]);

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );

        // برای کاربر موجود، session ثبت نام نباید ست بشه (چک دقیق‌تر می‌خوای بگو)
        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_otp', Mockery::type('string'))->once();
    }

    public function test_verifyOtpForMobile_success_creates_user_if_not_exists()
    {
        $mobile = '09125554433';
        $ip = '222.222.222.222';

        // ذخیره pending registration data در Cache به صورت رمزنگاری شده
        $registrationData = [
            'name' => 'Ali',
            'lastname' => 'Ahmadi',
            'mobile_number' => $mobile,
        ];

        Cache::put('pending_registration:' . hash('sha256', $mobile . config('app.key')), Crypt::encrypt($registrationData), now()->addMinutes(10));

        // تولید OTP و ذخیره
        $otp = $this->otpService->generateAndStoreOtp($mobile);

        $user = $this->otpService->verifyOtpForMobile(
            $mobile,
            $otp,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['mobile_number' => $mobile]);
        $this->assertEquals('Ali', $user->name);
    }

    public function test_verifyOtpForMobile_fails_with_invalid_otp()
    {
        $mobile = '09125554444';
        $ip = '10.10.10.10';

        $otp = $this->otpService->generateAndStoreOtp($mobile);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.');

        $this->otpService->verifyOtpForMobile(
            $mobile,
            '000000', // OTP اشتباه
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );
    }

    public function test_clearOtp_removes_otp_from_cache()
    {
        $mobile = '09127778899';

        $otp = $this->otpService->generateAndStoreOtp($mobile);

        $cacheKeyMethod = (new \ReflectionClass($this->otpService))->getMethod('getOtpCacheKey');
        $cacheKeyMethod->setAccessible(true);
        $cacheKey = $cacheKeyMethod->invokeArgs($this->otpService, [$mobile]);

        $this->assertNotNull(Cache::get($cacheKey));

        $this->otpService->clearOtp($mobile);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_sendOtpForMobile_throws_exception_when_rate_limit_exceeded()
    {
        $mobile = '09123334455';
        $ip = '8.8.8.8';

        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')
            ->once()
            ->andReturnFalse();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.');

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );
    }

    public function test_verifyOtpForMobile_throws_exception_when_rate_limit_exceeded()
    {
        $mobile = '09124445566';
        $ip = '7.7.7.7';

        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')
            ->once()
            ->andReturnTrue();

        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementVerifyAttempts')
            ->once()
            ->andReturnFalse();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('تعداد تلاش‌های تأیید کد بیش از حد مجاز است. لطفاً ۵ دقیقه دیگر تلاش کنید.');

        $this->otpService->verifyOtpForMobile(
            $mobile,
            '123456',
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );
    }
}
