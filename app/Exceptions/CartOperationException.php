<?php

namespace App\Exceptions;

use Exception; // Import the base Exception class

/**
 * Custom exception for cart-related operations.
 * This class can be used to throw specific exceptions
 * when an operation on the shopping cart fails due to
 * business logic or validation issues.
 */
class CartOperationException extends Exception
{
    // You can add custom properties or methods here if needed.
    // For example, to store specific error codes or data.
    // public function __construct($message = "", $code = 0, Throwable $previous = null)
    // {
    //     parent::__construct($message, $code, $previous);
    // }
}
