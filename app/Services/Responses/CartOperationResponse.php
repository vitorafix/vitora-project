<?php
// File: app/Services/Responses/CartOperationResponse.php

namespace App\Services\Responses;

use JsonSerializable;

/**
 * Class CartOperationResponse standardizes cart operation responses.
 * This is a DTO (Data Transfer Object) that encapsulates the result of an operation,
 * including its success status, a message, optional data, and an HTTP status code.
 */
class CartOperationResponse implements JsonSerializable
{
    /**
     * Constructor.
     *
     * @param bool $success Indicates whether the operation was successful.
     * @param string $message Describes the result of the operation.
     * @param mixed|null $data Optional related data.
     * @param int $statusCode HTTP status code.
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly int $statusCode = 200
    ) {}

    /**
     * Specify data to be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'status_code' => $this->statusCode,
        ];
    }

    /**
     * Factory method for a successful response.
     *
     * @param string $message
     * @param mixed|null $data
     * @param int $statusCode
     * @return static
     */
    public static function success(string $message, mixed $data = null, int $statusCode = 200): self
    {
        return new self(true, $message, $data, $statusCode);
    }

    /**
     * Factory method for a failed response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed|null $data
     * @return static
     */
    public static function fail(string $message, int $statusCode = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $statusCode);
    }
}
