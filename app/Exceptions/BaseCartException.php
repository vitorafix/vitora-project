<?php
// File: app/Exceptions/BaseCartException.php
namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all cart-related exceptions.
 * اکسپشن پایه برای تمام اکسپشن‌های مربوط به سبد خرید.
 */
class BaseCartException extends Exception
{
    public function __construct($message = "خطا در عملیات سبد خرید.", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/ProductNotFoundException.php
namespace App\Exceptions;

use Throwable;

class ProductNotFoundException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "محصول مورد نظر یافت نشد.", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/InsufficientStockException.php
namespace App\Exceptions;

use Throwable;

class InsufficientStockException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "موجودی کافی برای این محصول وجود ندارد.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/UnauthorizedCartAccessException.php
namespace App\Exceptions;

use Throwable;

class UnauthorizedCartAccessException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "شما اجازه دسترسی به این سبد خرید را ندارید.", $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartOperationException.php
namespace App\Exceptions;

use Throwable;

class CartOperationException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "خطا در عملیات سبد خرید.", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartInvalidArgumentException.php
namespace App\Exceptions;

use Throwable;

class CartInvalidArgumentException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "ورودی نامعتبر برای عملیات سبد خرید.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartLimitExceededException.php
namespace App\Exceptions;

use Throwable;

class CartLimitExceededException extends BaseCartException // بهبود: ارث‌بری از BaseCartException
{
    public function __construct($message = "محدودیت سبد خرید (تعداد آیتم یا مقدار) تجاوز کرده است.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
