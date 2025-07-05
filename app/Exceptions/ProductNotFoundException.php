// File: app/Exceptions/ProductNotFoundException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class ProductNotFoundException extends Exception
{
    public function __construct($message = "محصول مورد نظر یافت نشد.", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/InsufficientStockException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class InsufficientStockException extends Exception
{
    public function __construct($message = "موجودی کافی برای این محصول وجود ندارد.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/UnauthorizedCartAccessException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class UnauthorizedCartAccessException extends Exception
{
    public function __construct($message = "شما اجازه دسترسی به این سبد خرید را ندارید.", $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartOperationException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class CartOperationException extends Exception
{
    public function __construct($message = "خطا در عملیات سبد خرید.", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartInvalidArgumentException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class CartInvalidArgumentException extends Exception
{
    public function __construct($message = "ورودی نامعتبر برای عملیات سبد خرید.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// File: app/Exceptions/CartLimitExceededException.php
namespace App\Exceptions;

use Exception;
use Throwable;

class CartLimitExceededException extends Exception
{
    public function __construct($message = "محدودیت سبد خرید (تعداد آیتم یا مقدار) تجاوز کرده است.", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
