<?php

namespace App\Exceptions\Cart;

use App\Exceptions\BaseCartException;
use Throwable;

/**
 * @OA\Schema(
 * title="InsufficientStockException",
 * description="Exception thrown when there is insufficient stock for a product.",
 * @OA\Xml(
 * name="InsufficientStockException"
 * )
 * )
 */
class InsufficientStockException extends BaseCartException
{
    /**
     * Create a new InsufficientStockException instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct($message = "Insufficient stock for product.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

