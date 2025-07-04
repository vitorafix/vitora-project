<?php

namespace App\Contracts\Services;

use Illuminate\Http\Request;

interface AuditServiceInterface
{
    public function log(string $action, string $description, Request $request, ?string $mobileHash = null, ?int $userId = null): void;
}
