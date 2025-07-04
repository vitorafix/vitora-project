<?php

namespace App\Services;

use App\Contracts\Services\AuditServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditService implements AuditServiceInterface
{
    /**
     * Logs an audit event to the database.
     *
     * @param string $action The action performed (e.g., 'otp_send_success', 'user_login').
     * @param string $description A detailed description of the action.
     * @param Request $request The current HTTP request.
     * @param string|null $mobileHash Hashed mobile number, if applicable.
     * @param int|null $userId User ID, if applicable. Defaults to authenticated user's ID.
     * @return void
     */
    public function log(string $action, string $description, Request $request, ?string $mobileHash = null, ?int $userId = null): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null), // Use provided userId or authenticated user's ID, or null
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'mobile_hash' => $mobileHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::debug('Audit log recorded successfully', ['action' => $action, 'user_id' => $userId ?? (Auth::check() ? Auth::id() : null), 'ip' => $request->ip()]);
        } catch (\Exception $e) {
            Log::error('Failed to record audit log: ' . $e->getMessage(), [
                'action' => $action,
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'ip' => $request->ip(),
                'mobile_hash' => $mobileHash,
                'error' => $e->getMessage()
            ]);
        }
    }
}
