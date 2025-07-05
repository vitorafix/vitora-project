// File: app/Console/Commands/CleanupExpiredCartsCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Contracts\CartServiceInterface; // استفاده از اینترفیس

class CleanupExpiredCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:cleanup {--days= : Number of days to consider carts as expired (defaults to config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired guest carts.';

    protected CartServiceInterface $cartService;

    /**
     * Create a new command instance.
     *
     * @param CartServiceInterface $cartService
     * @return void
     */
    public function __construct(CartServiceInterface $cartService)
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
        $days = (int) $this->option('days'); // اگر --days=0 داده شود، 0 می‌شود
        if ($days === 0 && $this->option('days') !== '0') { // اگر آپشن داده نشده باشد یا مقدار معتبر نباشد
            $days = null; // استفاده از مقدار پیش‌فرض config
        }

        $this->info("Cleaning up carts older than " . ($days ?? config('cart.cleanup_days', 30)) . " days...");

        try {
            $deletedCount = $this->cartService->cleanupExpiredCarts($days);
            $this->info("Cleaned up {$deletedCount} expired carts successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during cart cleanup: " . $e->getMessage());
            Log::error("Cart cleanup command failed: " . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}
