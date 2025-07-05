<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Audit Service Interface
 * 
 * This interface defines the contract for audit logging services
 * to track user activities, system events, and security-related actions.
 * 
 * @package App\Contracts\Services
 * @author Your Name
 * @version 1.0.0
 */
interface AuditServiceInterface
{
    /**
     * Log a user action or system event
     *
     * @param string $action The action type (login, create, update, delete, etc.)
     * @param string $description Human-readable description of the action
     * @param Request $request The HTTP request object
     * @param array $metadata Additional data to store with the log
     * @param string|null $mobileHash Mobile device hash for tracking
     * @param int|null $userId User ID who performed the action
     * @param string|null $model The model class name if applicable
     * @param int|null $modelId The model ID if applicable
     * @param string $level Log level (info, warning, error, critical)
     * @return bool Returns true if log was successfully created
     */
    public function log(
        string $action,
        string $description,
        Request $request,
        array $metadata = [],
        ?string $mobileHash = null,
        ?int $userId = null,
        ?string $model = null,
        ?int $modelId = null,
        string $level = 'info'
    ): bool;

    /**
     * Log user authentication events
     *
     * @param int $userId User ID
     * @param string $action Authentication action (login, logout, failed_login)
     * @param Request $request The HTTP request object
     * @param array $metadata Additional authentication data
     * @return bool
     */
    public function logAuth(int $userId, string $action, Request $request, array $metadata = []): bool;

    /**
     * Log model-related actions (CRUD operations)
     *
     * @param string $model Model class name
     * @param int $modelId Model ID
     * @param string $action Action performed (created, updated, deleted, viewed)
     * @param Request $request The HTTP request object
     * @param array $oldValues Previous values (for updates)
     * @param array $newValues New values (for updates)
     * @param int|null $userId User who performed the action
     * @return bool
     */
    public function logModel(
        string $model,
        int $modelId,
        string $action,
        Request $request,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null
    ): bool;

    /**
     * Log security-related events
     *
     * @param string $event Security event type
     * @param string $description Event description
     * @param Request $request The HTTP request object
     * @param string $severity Severity level (low, medium, high, critical)
     * @param int|null $userId User ID if applicable
     * @return bool
     */
    public function logSecurity(
        string $event,
        string $description,
        Request $request,
        string $severity = 'medium',
        ?int $userId = null
    ): bool;

    /**
     * Log API access and usage
     *
     * @param string $endpoint API endpoint accessed
     * @param string $method HTTP method
     * @param Request $request The HTTP request object
     * @param int $responseCode HTTP response code
     * @param float $responseTime Response time in milliseconds
     * @param int|null $userId User ID if authenticated
     * @return bool
     */
    public function logApi(
        string $endpoint,
        string $method,
        Request $request,
        int $responseCode,
        float $responseTime,
        ?int $userId = null
    ): bool;

    /**
     * Log system errors and exceptions
     *
     * @param string $error Error message
     * @param string $exception Exception class name
     * @param Request $request The HTTP request object
     * @param array $context Additional error context
     * @param int|null $userId User ID if applicable
     * @return bool
     */
    public function logError(
        string $error,
        string $exception,
        Request $request,
        array $context = [],
        ?int $userId = null
    ): bool;

    /**
     * Get audit logs with filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param int $perPage Items per page
     * @param int $page Page number
     * @param string $sortBy Sort field
     * @param string $sortDirection Sort direction (asc, desc)
     * @return LengthAwarePaginator
     */
    public function getLogs(
        array $filters = [],
        int $perPage = 15,
        int $page = 1,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator;

    /**
     * Get audit logs for a specific user
     *
     * @param int $userId User ID
     * @param array $filters Additional filters
     * @param int $limit Number of logs to retrieve
     * @return Collection
     */
    public function getUserLogs(int $userId, array $filters = [], int $limit = 50): Collection;

    /**
     * Get audit logs for a specific model
     *
     * @param string $model Model class name
     * @param int $modelId Model ID
     * @param int $limit Number of logs to retrieve
     * @return Collection
     */
    public function getModelLogs(string $model, int $modelId, int $limit = 50): Collection;

    /**
     * Get recent audit logs
     *
     * @param int $hours Number of hours to look back
     * @param int $limit Number of logs to retrieve
     * @return Collection
     */
    public function getRecentLogs(int $hours = 24, int $limit = 100): Collection;

    /**
     * Get audit logs by action type
     *
     * @param string $action Action type
     * @param Carbon|null $startDate Start date filter
     * @param Carbon|null $endDate End date filter
     * @param int $limit Number of logs to retrieve
     * @return Collection
     */
    public function getLogsByAction(
        string $action,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 100
    ): Collection;

    /**
     * Get audit logs by IP address
     *
     * @param string $ipAddress IP address
     * @param int $limit Number of logs to retrieve
     * @return Collection
     */
    public function getLogsByIp(string $ipAddress, int $limit = 100): Collection;

    /**
     * Clean up old audit logs
     *
     * @param int $days Number of days to keep logs
     * @return int Number of deleted logs
     */
    public function cleanupOldLogs(int $days = 90): int;

    /**
     * Get audit statistics
     *
     * @param Carbon|null $startDate Start date for statistics
     * @param Carbon|null $endDate End date for statistics
     * @return array Statistics data
     */
    public function getStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array;

    /**
     * Export audit logs to CSV
     *
     * @param array $filters Filters to apply
     * @param Carbon|null $startDate Start date filter
     * @param Carbon|null $endDate End date filter
     * @return string CSV file path
     */
    public function exportToCsv(
        array $filters = [],
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): string;

    /**
     * Check if user has suspicious activity
     *
     * @param int $userId User ID
     * @param int $hours Time window in hours
     * @return bool
     */
    public function hasSuspiciousActivity(int $userId, int $hours = 1): bool;

    /**
     * Get failed login attempts for IP
     *
     * @param string $ipAddress IP address
     * @param int $hours Time window in hours
     * @return int Number of failed attempts
     */
    public function getFailedLoginAttempts(string $ipAddress, int $hours = 1): int;

    /**
     * Mark IP as suspicious
     *
     * @param string $ipAddress IP address
     * @param string $reason Reason for marking as suspicious
     * @param int|null $userId User ID if applicable
     * @return bool
     */
    public function markSuspiciousIp(string $ipAddress, string $reason, ?int $userId = null): bool;

    /**
     * Get audit log by ID
     *
     * @param int $logId Log ID
     * @return object|null
     */
    public function getLogById(int $logId): ?object;

    /**
     * Batch log multiple actions
     *
     * @param array $logs Array of log data
     * @return bool
     */
    public function batchLog(array $logs): bool;
}