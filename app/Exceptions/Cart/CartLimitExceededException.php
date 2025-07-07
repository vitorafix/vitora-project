<?php

namespace App\Exceptions\Cart;

use App\Exceptions\BaseCartException;
use Throwable;

/**
 * @OA\Schema(
 * title="CartLimitExceededException",
 * description="Exception thrown when a cart operation exceeds a defined limit (e.g., max items).",
 * @OA\Xml(
 * name="CartLimitExceededException"
 * )
 * )
 */
class CartLimitExceededException extends BaseCartException
{
    /**
     * Create a new CartLimitExceededException instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct($message = "Cart limit exceeded.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

