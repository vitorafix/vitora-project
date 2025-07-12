<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\Store as SessionStore;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected OtpService $otpService;
    protected $rateLimitServiceMock;
    protected $sessionMock;
    protected $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // شبیه‌سازی Crypt برای رمزنگاری و رمزگشایی (برای تست)
        Crypt::shouldReceive('encryptString')->andReturnUsing(fn($value) => base64_encode(serialize($value)));
        Crypt::shouldReceive('decryptString')->andReturnUsing(fn($value) => unserialize(base64_decode($value)));

        Cache::flush();

        $this->otpService = new OtpService();

        $this->rateLimitServiceMock = Mockery::mock('App\Contracts\Services\RateLimitServiceInterface');
        // پیش‌فرض همه‌ی متدها true برگردانند
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')->andReturn(true)
            ->shouldReceive('checkAndIncrementSendAttempts')->andReturn(true)
            ->shouldReceive('checkAndIncrementVerifyAttempts')->andReturn(true)
            ->shouldReceive('resetVerifyAttempts')->andReturnNull()
            ->shouldReceive('resetIpAttempts')->andReturnNull();

        $this->sessionMock = Mockery::mock(SessionStore::class);
        $this->sessionMock->shouldReceive('put')->andReturnNull();

        $this->auditLogger = function ($message, $level = 'info', $userId = null, $model = null, $modelId = null) {
            Log::info("AuditLog: [$level] $message");
        };
    }

    protected function tearDown(): void
    {
        // اگر تراکنش باز هست آن را ببند
        while ( \DB::transactionLevel() > 0 ) {
            \DB::rollBack();
        }

        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function generate_and_store_otp_and_verify_success(): void
    {
        $mobile = '09123456789';

        $otp = $this->otpService->generateAndStoreOtp($mobile);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        $verified = $this->otpService->verifyOtp($mobile, $otp);

        $this->assertTrue($verified);
    }

    #[Test]
    public function sendOtpForMobile_for_new_user(): void
    {
        $mobile = '09129998877';
        $ip = '123.123.123.123';

        $this->assertDatabaseMissing('users', ['mobile_number' => $mobile]);

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );

        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_registration', Mockery::type('string'))->once();
        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_otp', Mockery::type('string'))->once();

        $this->assertTrue(true);
    }

    #[Test]
    public function sendOtpForMobile_for_existing_user(): void
    {
        $mobile = '09120001122';
        $ip = '111.111.111.111';

        $user = User::factory()->create(['mobile_number' => $mobile]);

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );

        $this->sessionMock->shouldHaveReceived('put')->with('mobile_number_for_otp', Mockery::type('string'))->once();

        $this->assertNotNull($user);
    }

    #[Test]
    public function verifyOtpForMobile_fails_with_invalid_otp(): void
    {
        $mobile = '09125554444';
        $ip = '10.10.10.10';

        $this->otpService->generateAndStoreOtp($mobile);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.');

        $this->otpService->verifyOtpForMobile(
            $mobile,
            '000000',
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );
    }

    #[Test]
    public function clearOtp_removes_otp_from_cache(): void
    {
        $mobile = '09127778899';

        $this->otpService->generateAndStoreOtp($mobile);

        $cacheKeyMethod = (new \ReflectionClass($this->otpService))->getMethod('getOtpCacheKey');
        $cacheKeyMethod->setAccessible(true);
        $cacheKey = $cacheKeyMethod->invokeArgs($this->otpService, [$mobile]);

        $this->assertNotNull(Cache::get($cacheKey));

        $this->otpService->clearOtp($mobile);

        $this->assertNull(Cache::get($cacheKey));
    }

    #[Test]
    public function send_otp_for_mobile_throws_exception_when_rate_limit_exceeded(): void
    {
        $mobile = '09123334455';
        $ip = '8.8.8.8';

        // مقداردهی mock برای اینکه rate limit ارسال از موبایل رد شده باشد (false)
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')->once()->andReturnTrue();
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementSendAttempts')->once()->andReturnFalse();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('تعداد درخواست‌های ارسال کد بیش از حد مجاز است.');

        $this->otpService->sendOtpForMobile(
            $mobile,
            $ip,
            $this->sessionMock,
            $this->rateLimitServiceMock,
            $this->auditLogger
        );
    }

    #[Test]
    public function verify_otp_for_mobile_throws_exception_when_rate_limit_exceeded(): void
    {
        $mobile = '09124445566';
        $ip = '7.7.7.7';

        $this->otpService->generateAndStoreOtp($mobile);

        // مقداردهی mock برای رد شدن rate limit تایید OTP
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementIpAttempts')->once()->andReturnTrue();
        $this->rateLimitServiceMock
            ->shouldReceive('checkAndIncrementVerifyAttempts')->once()->andReturnFalse();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('تعداد تلاش‌های تأیید کد بیش از حد مجاز است.');

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
