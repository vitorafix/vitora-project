<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log; // اضافه کردن این خط

class TestOtpFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:otp {mobile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OTP flow with debug logs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mobile = $this->argument('mobile');
        $otpService = app(OtpService::class);
        
        $this->info("Testing OTP for mobile: {$mobile}");
        Log::info("TestOtpFlow Command: Starting test for mobile: {$mobile}"); // لاگ شروع کامند
        
        // تولید
        $otp = $otpService->generateAndStoreOtp($mobile); // استفاده از متد جدید
        $this->info("Generated OTP: {$otp}");
        Log::info("TestOtpFlow Command: Generated OTP: {$otp} for mobile: {$mobile}"); // لاگ OTP تولید شده
        
        // تأخیر
        sleep(2); // تأخیر بیشتر برای شبیه‌سازی تأخیر کاربر
        $this->info("Waiting 2 seconds...");
        Log::info("TestOtpFlow Command: Waited 2 seconds for mobile: {$mobile}"); // لاگ تأخیر
        
        // تأیید
        $result = $otpService->verifyOtp($mobile, $otp);
        $this->info("Verification result: " . ($result ? 'SUCCESS' : 'FAILED'));
        Log::info("TestOtpFlow Command: Verification result: " . ($result ? 'SUCCESS' : 'FAILED') . " for mobile: {$mobile}"); // لاگ نتیجه تأیید
        
        return $result ? 0 : 1;
    }
}
