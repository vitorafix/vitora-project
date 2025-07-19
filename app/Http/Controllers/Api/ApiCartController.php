<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem;
use App\Models\Product;

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Services\CartCalculationService;

// Form Requests (if you decide to use them for API validation)
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\ApplyCouponRequest;

// Custom Exceptions
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartOperationException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\InsufficientStockException;
use App\Http\Resources\CartResource; // Assuming CartResource exists and is needed

// Renamed from CartController to ApiCartController for clarity
class ApiCartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CartCalculationService $cartCalculationService;

    public function __construct(CartServiceInterface $cartService, CartCalculationService $cartCalculationService)
    {
        $this->cartService = $cartService;
        $this->cartCalculationService = $cartCalculationService;
    }

    /**
     * Get the current cart contents as JSON.
     * This method is called by the frontend JavaScript to display cart items.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContents(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling getOrCreateCart
            Log::debug('ApiCartController::getContents: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            // Pass user, sessionId, and guestUuid to getOrCreateCart
            $cart = $this->cartService->getOrCreateCart($user, $sessionId, $guestUuid);

            $cartContentsResponse = $this->cartService->getCartContents($cart);

            return (new CartResource($cartContentsResponse))
                ->additional([
                    'success' => true,
                    'message' => 'Cart contents successfully loaded.',
                ])
                ->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            Log::error('Error fetching cart contents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading cart contents.',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Add a product to the cart or update its quantity via API.
     *
     * @param  \App\Models\Product  $product
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Product $product, Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'nullable|integer|min:1',
            ]);

            $quantity = $request->input('quantity', 1);

            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling getOrCreateCart
            Log::debug('ApiCartController::add: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            // Pass user, sessionId, and guestUuid
            $currentCart = $this->cartService->getOrCreateCart($user, $sessionId, $guestUuid);

            $response = $this->cartService->addOrUpdateCartItem(
                $currentCart,
                $product->id,
                $quantity
            );

            if ($response->isSuccess()) {
                $updatedCart = $currentCart->fresh();
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart);

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'Product successfully added to cart.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error adding item to cart: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error. Please enter a valid quantity.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ProductNotFoundException $e) {
            Log::error('Product not found for cart add operation: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'The requested product was not found.',
            ], 404);
        } catch (InsufficientStockException $e) {
            Log::error('Insufficient stock error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error adding item to cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'System error adding product to cart. Please contact support.',
            ], 500);
        }
    }

    /**
     * Update the quantity of a cart item via API.
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling userOwnsCartItem
            Log::debug('ApiCartController::updateQuantity: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId, $guestUuid)) { // پاس دادن guestUuid
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $request->validate([
                'quantity' => 'required|integer|min:0',
            ]);

            $newQuantity = $request->input('quantity');

            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId, $guestUuid); // پاس دادن guestUuid

            if ($response->isSuccess()) {
                $cart = $this->cartService->getCartById($cartItem->cart_id, $user, $sessionId, $guestUuid); // پاس دادن guestUuid
                if (!$cart) {
                    throw new CartOperationException('Cart associated with item not found.', 404);
                }
                $cartContentsResponse = $this->cartService->getCartContents($cart->fresh());

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'Cart item quantity successfully updated.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating cart item quantity: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error. Please enter a valid quantity.',
                'errors' => $e->errors(),
            ], 422);
        } catch (UnauthorizedCartAccessException $e) {
            Log::error('Unauthorized cart access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (InsufficientStockException $e) {
            Log::error('Insufficient stock error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Error updating cart item quantity: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error updating cart item quantity. Please contact support.'], 500);
        }
    }

    /**
     * Remove an item from the cart via API.
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem(Request $request, CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling userOwnsCartItem
            Log::debug('ApiCartController::removeCartItem: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId, $guestUuid)) { // پاس دادن guestUuid
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId, $guestUuid); // پاس دادن guestUuid

            if ($response->isSuccess()) {
                $updatedCart = $cartItem->cart->fresh();
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart);

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'Item successfully removed from cart.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (UnauthorizedCartAccessException $e) {
            Log::error('Unauthorized cart access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Error removing item from cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error removing item from cart. Please contact support.'], 500);
        }
    }

    /**
     * Clear all items from the cart via API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $sessionId = Session::getId();
        $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

        // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling getOrCreateCart
        Log::debug('ApiCartController::clearCart: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

        try {
            $cart = $this->cartService->getOrCreateCart($user, $sessionId, $guestUuid); // پاس دادن guestUuid
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                $updatedCart = $cart->fresh();
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart);

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'Cart successfully cleared.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Throwable $e) {
            Log::error('Error clearing cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'System error clearing cart. Please contact support.'], 500);
        }
    }

    /**
     * Apply a coupon to the cart via API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'coupon_code' => 'required|string|max:255',
            ]);

            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling getOrCreateCart
            Log::debug('ApiCartController::applyCoupon: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            // Pass user, sessionId, and guestUuid
            $cart = $this->cartService->getOrCreateCart($user, $sessionId, $guestUuid);

            $couponCode = $request->input('coupon_code');
            $response = $this->cartService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                $updatedCart = $cart->fresh();
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart);

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'Coupon successfully applied.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error applying coupon: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error for coupon code. Please enter a valid code.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error applying coupon: ' . $e->getMessage(), ['coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error applying coupon. Please contact support.'], 500);
        }
    }

    /**
     * Remove a coupon from the cart via API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $guestUuid = $request->header('X-Guest-UUID'); // دریافت guest_uuid از هدر

            // DEBUG LOG: Check Auth::user() and Session::getId() and guestUuid before calling getOrCreateCart
            Log::debug('ApiCartController::removeCoupon: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

            try {
                // Pass user, sessionId, and guestUuid
                $cart = $this->cartService->getOrCreateCart($user, $sessionId, $guestUuid);
                $response = $this->cartService->removeCoupon($cart);

                if ($response->isSuccess()) {
                    $updatedCart = $cart->fresh();
                    $cartContentsResponse = $this->cartService->getCartContents($updatedCart);

                    return (new CartResource($cartContentsResponse))
                        ->additional([
                            'success' => true,
                            'message' => 'Coupon successfully removed.',
                        ])
                        ->response()
                        ->setStatusCode(200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $response->getMessage(),
                    ], $response->getStatusCode());
                }
            } catch (\Throwable $e) {
                Log::error('API remove coupon error: ' . $e->getMessage(), ['exception' => $e]);
                return response()->json(['success' => false, 'message' => 'Error removing coupon.'], 500); // Changed to JSON response
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error removing coupon. Please contact support.'], 500);
        }
    }
}
