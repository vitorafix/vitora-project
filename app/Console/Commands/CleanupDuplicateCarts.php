<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use App\Services\CartService; // استفاده از CartService
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:cleanup-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up duplicate guest carts and merges items into the latest one.';

    /**
     * The CartService instance.
     *
     * @var CartService
     */
    protected CartService $cartService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CartService $cartService)
    {
        parent::__construct();
        $this->cartService = $cartService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting cleanup of duplicate guest carts...');
        $cleanedCount = 0;

        try {
            // 1. پیدا کردن guest_uuid های تکراری
            $duplicateGuestUuids = Cart::select('guest_uuid')
                ->where('user_id', 'guest') // فقط سبدهای مهمان
                ->whereNotNull('guest_uuid') // فقط آنهایی که guest_uuid دارند
                ->groupBy('guest_uuid')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('guest_uuid'); // فقط مقادیر guest_uuid را بگیرید

            if ($duplicateGuestUuids->isEmpty()) {
                $this->info('No duplicate guest carts found by guest_uuid.');
            } else {
                $this->info('Found ' . $duplicateGuestUuids->count() . ' duplicate guest UUIDs.');
                foreach ($duplicateGuestUuids as $guestUuid) {
                    $this->info("Processing duplicate guest_uuid: {$guestUuid}");

                    // گرفتن تمام سبدهای مرتبط با این guest_uuid، مرتب شده بر اساس updated_at نزولی
                    $cartsToProcess = Cart::where('guest_uuid', $guestUuid)
                        ->where('user_id', 'guest')
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    // جدیدترین سبد خرید را نگه می‌داریم
                    $keepCart = $cartsToProcess->first();
                    // بقیه سبدها را برای حذف یا ادغام انتخاب می‌کنیم
                    $deleteCarts = $cartsToProcess->skip(1);

                    if ($keepCart && !$deleteCarts->isEmpty()) {
                        $this->info("Keeping cart ID: {$keepCart->id} for guest_uuid: {$guestUuid}");
                        foreach ($deleteCarts as $cartToDelete) {
                            DB::beginTransaction();
                            try {
                                $this->info("Attempting to merge/delete duplicate cart ID: {$cartToDelete->id}");
                                // از متد mergeCarts در CartService برای ادغام استفاده کنید
                                // این متد آیتم‌ها را منتقل کرده و سبد مبدا را حذف می‌کند.
                                $this->cartService->mergeCarts($keepCart, $cartToDelete);
                                $cleanedCount++;
                                DB::commit();
                                $this->info("Successfully merged and deleted cart ID: {$cartToDelete->id}");
                            } catch (\Throwable $e) {
                                DB::rollBack();
                                Log::error('Error merging/deleting duplicate guest cart during cleanup.', [
                                    'guest_uuid' => $guestUuid,
                                    'cart_to_delete_id' => $cartToDelete->id,
                                    'exception' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                $this->error("Failed to merge/delete cart ID: {$cartToDelete->id}. Error: " . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // 2. پیدا کردن user_id های تکراری (برای اطمینان، اگرچه در حالت عادی نباید اتفاق بیفتد)
            // این سناریو کمتر محتمل است اگر unique index روی user_id به درستی کار کند،
            // اما برای پوشش موارد خاص و پاکسازی داده‌های قدیمی مفید است.
            $duplicateUserIds = Cart::select('user_id')
                ->whereNotNull('user_id')
                ->where('user_id', '!=', 'guest') // فقط کاربران لاگین شده
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_id');

            if ($duplicateUserIds->isEmpty()) {
                $this->info('No duplicate user carts found by user_id.');
            } else {
                $this->info('Found ' . $duplicateUserIds->count() . ' duplicate user IDs.');
                foreach ($duplicateUserIds as $userId) {
                    $this->info("Processing duplicate user_id: {$userId}");

                    $cartsToProcess = Cart::where('user_id', $userId)
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    $keepCart = $cartsToProcess->first();
                    $deleteCarts = $cartsToProcess->skip(1);

                    if ($keepCart && !$deleteCarts->isEmpty()) {
                        $this->info("Keeping cart ID: {$keepCart->id} for user_id: {$userId}");
                        foreach ($deleteCarts as $cartToDelete) {
                            DB::beginTransaction();
                            try {
                                $this->info("Attempting to merge/delete duplicate user cart ID: {$cartToDelete->id}");
                                $this->cartService->mergeCarts($keepCart, $cartToDelete);
                                $cleanedCount++;
                                DB::commit();
                                $this->info("Successfully merged and deleted cart ID: {$cartToDelete->id}");
                            } catch (\Throwable $e) {
                                DB::rollBack();
                                Log::error('Error merging/deleting duplicate user cart during cleanup.', [
                                    'user_id' => $userId,
                                    'cart_to_delete_id' => $cartToDelete->id,
                                    'exception' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                $this->error("Failed to merge/delete cart ID: {$cartToDelete->id}. Error: " . $e->getMessage());
                            }
                        }
                    }
                }
            }


            $this->info("Cleanup completed. Total carts cleaned up: {$cleanedCount}");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Error in CleanupDuplicateCarts command: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->error('An error occurred during cleanup: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
