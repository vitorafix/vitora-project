<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartMetricsManager;
use Illuminate\Contracts\Events\Dispatcher; // If you have CartMerged event

class CartMergeService
{
    protected CartRepositoryInterface $cartRepository;
    protected CartCacheManager $cacheManager;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartCacheManager $cacheManager,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher
    ) {
        $this->cartRepository = $cartRepository;
        $this->cacheManager = $cacheManager;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Merge a guest cart into a user's cart upon login.
     * ادغام سبد خرید مهمان با سبد خرید کاربر پس از ورود.
     *
     * @param \App\Models\User $user
     * @param string $guestSessionId
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to merge guest cart to user cart.', ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);

        DB::beginTransaction();
        try {
            $userCart = $this->cartRepository->findByUserId($user->id);
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId);

            if (!$guestCart || $guestCart->items->isEmpty()) {
                Log::info('No guest cart or guest cart is empty to merge.', ['guest_session_id' => $guestSessionId]);
                DB::rollBack(); // Nothing to merge, so rollback
                return;
            }

            if (!$userCart) {
                // If user doesn't have a cart, assign the guest cart to the user
                $guestCart->user_id = $user->id;
                $guestCart->session_id = null;
                $this->cartRepository->save($guestCart);
                Log::info('Guest cart assigned to user as user had no cart.', ['user_id' => $user->id, 'guest_cart_id' => $guestCart->id]);
            } else {
                // Merge items from guest cart to user cart
                foreach ($guestCart->items as $guestItem) {
                    $existingUserItem = $this->cartRepository->findCartItem(
                        $userCart->id,
                        $guestItem->product_id,
                        $guestItem->product_variant_id // Include variant ID for merge
                    );

                    if ($existingUserItem) {
                        // Update quantity if item already exists in user's cart
                        $existingUserItem->quantity += $guestItem->quantity;
                        $this->cartRepository->updateCartItem($existingUserItem, ['quantity' => $existingUserItem->quantity]);
                        Log::info('Merged existing cart item quantity.', ['user_cart_item_id' => $existingUserItem->id, 'product_id' => $guestItem->product_id, 'quantity' => $guestItem->quantity]);
                    } else {
                        // Move item to user's cart
                        $guestItem->cart_id = $userCart->id;
                        $this->cartRepository->saveCartItem($guestItem); // This should update the cart_id
                        Log::info('Moved new cart item from guest to user cart.', ['guest_cart_item_id' => $guestItem->id, 'product_id' => $guestItem->product_id]);
                    }
                }
                // Delete the guest cart after merging its items
                $this->cartRepository->delete($guestCart);
                Log::info('Guest cart deleted after merge.', ['guest_cart_id' => $guestCart->id]);
            }

            DB::commit();
            $this->cacheManager->clearCache($user); // Clear user's cart cache
            $this->cacheManager->clearCache(null, $guestSessionId); // Clear guest cart cache
            $this->metricsManager->recordMetric('mergeGuestCart_duration', microtime(true) - $startTime, ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);
            $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $userCart ?? $guestCart, $user)); // Dispatch event

            Log::info('Guest cart successfully merged to user cart.', ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error merging guest cart: ' . $e->getMessage(), ['user_id' => $user->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('mergeGuestCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw $e; // Re-throw for higher-level handling
        }
    }

    /**
     * Assigns a guest cart to a newly registered user.
     * اختصاص سبد خرید مهمان به کاربر تازه ثبت نام شده.
     *
     * @param string $guestSessionId
     * @param \App\Models\User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to assign guest cart to new user.', ['new_user_id' => $newUser->id, 'guest_session_id' => $guestSessionId]);

        DB::beginTransaction();
        try {
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId);

            if (!$guestCart) {
                Log::info('No guest cart found to assign to new user.', ['guest_session_id' => $guestSessionId]);
                DB::rollBack();
                return;
            }

            $guestCart->user_id = $newUser->id;
            $guestCart->session_id = null;
            $this->cartRepository->save($guestCart);

            DB::commit();
            $this->cacheManager->clearCache($newUser); // Clear new user's cart cache
            $this->cacheManager->clearCache(null, $guestSessionId); // Clear guest cart cache
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_duration', microtime(true) - $startTime, ['new_user_id' => $newUser->id, 'guest_session_id' => $guestSessionId]);
            $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $guestCart, $newUser)); // Dispatch event (can reuse CartMerged)

            Log::info('Guest cart successfully assigned to new user.', ['new_user_id' => $newUser->id, 'guest_cart_id' => $guestCart->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error assigning guest cart to new user: ' . $e->getMessage(), ['new_user_id' => $newUser->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw $e;
        }
    }
}

