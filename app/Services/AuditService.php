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
     * @param string $action
     * @param string $description
     * @param Request|null $request
     * @param string|null $mobileNumber
     * @param int|null $userId
     * @param array $extraData Additional optional fields to store in audit_logs
     * @return void
     */
    public function log(
        string $action,
        string $description,
        ?Request $request = null,
        ?string $mobileNumber = null,
        ?int $userId = null,
        array $extraData = []
    ): void {
        try {
            $ip = $request ? $request->ip() : null;
            $userAgent = $request ? $request->userAgent() : null;
            $sessionId = $request && $request->session() ? $request->session()->getId() : null;

            $data = array_merge([
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'action' => $action,
                'description' => $description,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'mobile_hash' => $mobileNumber ? hash('sha256', $mobileNumber) : null,
                'session_id' => $sessionId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $extraData);

            DB::table('audit_logs')->insert($data);

            Log::debug('Audit log recorded successfully', [
                'action' => $action,
                'user_id' => $data['user_id'],
                'ip' => $ip,
                'extra' => $extraData,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record audit log: ' . $e->getMessage(), [
                'action' => $action,
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'ip' => $ip ?? 'unknown',
                'mobile_hash' => $mobileNumber ? hash('sha256', $mobileNumber) : null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
