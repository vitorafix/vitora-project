<?php

namespace App\Exceptions;

use Exception;

class EmptyCartException extends Exception
{
    // این یک استثنای سفارشی برای زمانی است که سبد خرید خالی است.
    // This is a custom exception for when the cart is empty.
    public function __construct(string $message = "The cart is empty.", int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
