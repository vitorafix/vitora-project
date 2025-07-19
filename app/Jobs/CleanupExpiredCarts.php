<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Contracts\CartServiceInterface; // Import the CartServiceInterface
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of days after which guest carts should be considered expired.
     * تعداد روزهایی که پس از آن سبدهای خرید مهمان منقضی شده در نظر گرفته می‌شوند.
     * @var int
     */
    protected $daysCutoff;

    /**
     * Create a new job instance.
     * یک نمونه Job جدید ایجاد کنید.
     *
     * @param int $daysCutoff - تعداد روزهایی که برای انقضا در نظر گرفته می‌شود.
     * @return void
     */
    public function __construct(int $daysCutoff = 30)
    {
        $this->daysCutoff = $daysCutoff;
    }

    /**
     * Execute the job.
     * اجرای Job.
     *
     * @param \App\Services\Contracts\CartServiceInterface $cartService
     * @return void
     */
    public function handle(CartServiceInterface $cartService)
    {
        Log::info('Starting CleanupExpiredCarts job.', ['days_cutoff' => $this->daysCutoff]);

        try {
            // Calculate the cutoff date
            $cutoffDate = Carbon::now()->subDays($this->daysCutoff);

            // Get expired guest carts using the CartRepository (via CartService)
            // سبدهای خرید مهمان منقضی شده را با استفاده از CartRepository (از طریق CartService) دریافت کنید.
            // Note: We need a method in CartRepository to get expired carts.
            // توجه: ما به یک متد در CartRepository برای دریافت سبدهای منقضی شده نیاز داریم.
            // Assuming CartRepositoryInterface has getExpiredGuestCarts method.
            $cleanedCount = $cartService->cleanupExpiredCarts($this->daysCutoff);

            Log::info('CleanupExpiredCarts job completed.', ['cleaned_carts_count' => $cleanedCount]);
        } catch (\Throwable $e) {
            Log::error('Error in CleanupExpiredCarts job: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            // Re-throw the exception if you want the job to be retried
            // اگر می‌خواهید Job دوباره امتحان شود، استثنا را دوباره پرتاب کنید.
            throw $e;
        }
    }
}
