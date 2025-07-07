<?php

namespace App\Services\Contracts;

use App\Models\Cart;
use Carbon\Carbon;

/**
 * Interface for Cart Cleanup Service.
 * رابط برای سرویس پاکسازی سبد خرید.
 */
interface CartCleanupServiceInterface
{
    /**
     * Clean up expired guest carts.
     * پاکسازی سبدهای خرید مهمان منقضی شده.
     *
     * @param int|null $daysCutoff The number of days after which a guest cart is considered expired.
     * @return int The number of carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int;
}

