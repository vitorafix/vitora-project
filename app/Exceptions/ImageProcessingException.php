<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ImageProcessingException extends Exception
{
    // انواع خطاهای مختلف
    public const ERROR_INVALID_FORMAT = 'INVALID_FORMAT';
    public const ERROR_FILE_TOO_LARGE = 'FILE_TOO_LARGE';
    public const ERROR_UPLOAD_FAILED = 'UPLOAD_FAILED';
    public const ERROR_RESIZE_FAILED = 'RESIZE_FAILED';
    public const ERROR_QUALITY_FAILED = 'QUALITY_FAILED';
    public const ERROR_WATERMARK_FAILED = 'WATERMARK_FAILED';
    public const ERROR_SAVE_FAILED = 'SAVE_FAILED';
    public const ERROR_DELETE_FAILED = 'DELETE_FAILED'; // Added for clarity
    public const ERROR_INVALID_DIMENSIONS = 'INVALID_DIMENSIONS';
    public const ERROR_CORRUPTED_FILE = 'CORRUPTED_FILE';

    protected string $errorType;
    protected array $context;

    public function __construct(
        string $message = '',
        string $errorType = '',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorType = $errorType;
        $this->context = $context;
    }

    /**
     * دریافت نوع خطا
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * دریافت context خطا
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * تولید exception برای فرمت نامعتبر
     */
    public static function invalidFormat(string $format, array $allowedFormats = []): self
    {
        $message = "فرمت تصویر '{$format}' پشتیبانی نمی‌شود";
        
        if (!empty($allowedFormats)) {
            $message .= '. فرمت‌های مجاز: ' . implode(', ', $allowedFormats);
        }

        return new self(
            message: $message,
            errorType: self::ERROR_INVALID_FORMAT,
            context: [
                'format' => $format,
                'allowed_formats' => $allowedFormats
            ]
        );
    }

    /**
     * تولید exception برای حجم بالای فایل
     */
    public static function fileTooLarge(int $fileSize, int $maxSize): self
    {
        $fileSizeMB = round($fileSize / (1024 * 1024), 2);
        $maxSizeMB = round($maxSize / (1024 * 1024), 2);

        return new self(
            message: "حجم فایل ({$fileSizeMB}MB) بیش از حد مجاز ({$maxSizeMB}MB) است",
            errorType: self::ERROR_FILE_TOO_LARGE,
            context: [
                'file_size' => $fileSize,
                'max_size' => $maxSize,
                'file_size_mb' => $fileSizeMB,
                'max_size_mb' => $maxSizeMB
            ]
        );
    }

    /**
     * تولید exception برای خطا در آپلود
     */
    public static function uploadFailed(string $reason = ''): self
    {
        $message = 'آپلود فایل با خطا مواجه شد';
        
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self(
            message: $message,
            errorType: self::ERROR_UPLOAD_FAILED,
            context: ['reason' => $reason]
        );
    }

    /**
     * تولید exception برای خطا در تغییر اندازه
     */
    public static function resizeFailed(int $width, int $height, string $reason = ''): self
    {
        $message = "تغییر اندازه تصویر به {$width}x{$height} انجام نشد";
        
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self(
            message: $message,
            errorType: self::ERROR_RESIZE_FAILED,
            context: [
                'target_width' => $width,
                'target_height' => $height,
                'reason' => $reason
            ]
        );
    }
    
    /**
     * تولید exception برای خطا در ذخیره فایل
     */
    public static function saveFailed(string $reason = ''): self
    {
        $message = 'ذخیره فایل تصویر با خطا مواجه شد';
        
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self(
            message: $message,
            errorType: self::ERROR_SAVE_FAILED,
            context: ['reason' => $reason]
        );
    }

    /**
     * تولید exception برای خطا در حذف فایل
     */
    public static function deleteFailed(string $reason = ''): self
    {
        $message = 'حذف فایل تصویر با خطا مواجه شد';
        
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self(
            message: $message,
            errorType: self::ERROR_DELETE_FAILED,
            context: ['reason' => $reason]
        );
    }

    /**
     * تولید exception برای ابعاد نامعتبر
     */
    public static function invalidDimensions(int $width, int $height, int $maxWidth, int $maxHeight): self
    {
        return new self(
            message: "ابعاد تصویر ({$width}x{$height}) بیش از حد مجاز ({$maxWidth}x{$maxHeight}) است",
            errorType: self::ERROR_INVALID_DIMENSIONS,
            context: [
                'width' => $width,
                'height' => $height,
                'max_width' => $maxWidth,
                'max_height' => $maxHeight
            ]
        );
    }

    /**
     * تولید exception برای فایل خراب
     */
    public static function corruptedFile(string $filename = ''): self
    {
        $message = 'فایل تصویر خراب یا نامعتبر است';
        
        if ($filename) {
            $message .= ": {$filename}";
        }

        return new self(
            message: $message,
            errorType: self::ERROR_CORRUPTED_FILE,
            context: ['filename' => $filename]
        );
    }

    /**
     * تبدیل به آرایه برای لاگ یا API response
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'error_type' => $this->errorType,
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }

    /**
     * تبدیل به JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
