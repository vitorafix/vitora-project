<?php

namespace App\Services;

use App\Contracts\Services\AuditServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog; // Assuming you have an AuditLog model for DB operations, or directly use DB::table

class AuditService implements AuditServiceInterface
{
    /**
     * Log a user action or system event
     *
     * @param string $action The action type (login, create, update, delete, etc.)
     * @param string $description Human-readable description of the action
     * @param Request $request The HTTP request object (Now required, not nullable)
     * @param array $metadata Additional data to store with the log (Now the 4th argument)
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
        Request $request, // Changed: Must be Illuminate\Http\Request and not nullable
        array $metadata = [], // Changed: Name and type of the 4th argument
        ?string $mobileHash = null,
        ?int $userId = null,
        ?string $model = null, // Added: New argument from interface
        ?int $modelId = null, // Added: New argument from interface
        string $level = 'info' // Added: New argument from interface
    ): bool { // Changed: Return type to bool
        try {
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $sessionId = $request->session() ? $request->session()->getId() : null;

            // Ensure mobileHash is used if provided, otherwise derive from metadata if present
            $finalMobileHash = $mobileHash ?? ($metadata['mobile_number'] ?? null ? hash('sha256', $metadata['mobile_number']) : null);

            // Using AuditLog model if it exists, otherwise DB::table
            // Assuming AuditLog model has fillable fields for these.
            // If you don't have an AuditLog model, replace with DB::table('audit_logs')->insert(...)
            AuditLog::create([
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'action' => $action,
                'description' => $description,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'session_id' => $sessionId,
                'mobile_hash' => $finalMobileHash,
                'metadata' => json_encode($metadata), // Store metadata as JSON
                'model_type' => $model,
                'model_id' => $modelId,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::debug('Audit log recorded successfully', [
                'action' => $action,
                'user_id' => $userId,
                'ip' => $ip,
                'extra' => $metadata,
            ]);

            return true; // Successfully logged
        } catch (\Exception $e) {
            Log::error('Failed to record audit log: ' . $e->getMessage(), [
                'action' => $action,
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'ip' => $request->ip() ?? 'unknown',
                'error' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(), // Added for more detailed debugging
            ]);
            return false; // Logging failed
        }
    }

    // You might also need to implement other methods from AuditServiceInterface
    // if they are called elsewhere in your application.
    // For example: logAuth, logModel, logSecurity, logApi, logError, getLogs, etc.
    // If these are not implemented, and your application tries to call them,
    // you will get similar FatalError messages.
    // For now, we only fix the 'log' method as per the error provided.

    // Example placeholder for other methods to avoid FatalError if called:
    public function logAuth(int $userId, string $action, Request $request, array $metadata = []): bool { return false; }
    public function logModel(string $model, int $modelId, string $action, Request $request, array $oldValues = [], array $newValues = [], ?int $userId = null): bool { return false; }
    public function logSecurity(string $event, string $description, Request $request, string $severity = 'medium', ?int $userId = null): bool { return false; }
    public function logApi(string $endpoint, string $method, Request $request, int $responseCode, float $responseTime, ?int $userId = null): bool { return false; }
    public function logError(string $error, string $exception, Request $request, array $context = [], ?int $userId = null): bool { return false; }

    // Placeholder for methods that return collections or paginators,
    // these need proper implementation if used.
    public function getLogs(array $filters = [], int $perPage = 15, int $page = 1, string $sortBy = 'created_at', string $sortDirection = 'desc'): \Illuminate\Pagination\LengthAwarePaginator { return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page); }
    public function getUserLogs(int $userId, array $filters = [], int $limit = 50): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getModelLogs(string $model, int $modelId, int $limit = 50): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getRecentLogs(int $hours = 24, int $limit = 100): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getLogsByAction(string $action, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null, int $limit = 100): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getLogsByIp(string $ipAddress, int $limit = 100): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function cleanupOldLogs(int $days = 90): int { return 0; }
    public function getStatistics(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array { return []; }
    public function exportToCsv(array $filters = [], ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): string { return ''; }
    public function hasSuspiciousActivity(int $userId, int $hours = 1): bool { return false; }
    public function getFailedLoginAttempts(string $ipAddress, int $hours = 1): int { return 0; }
    public function markSuspiciousIp(string $ipAddress, string $reason, ?int $userId = null): bool { return false; }
    public function getLogById(int $logId): ?object { return null; }
    public function batchLog(array $logs): bool { return false; }
}
