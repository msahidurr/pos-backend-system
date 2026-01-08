<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verify authenticated user belongs to the requested tenant
        if (auth()->check()) {
            $tenantId = $request->header('X-Tenant-ID');
            $userTenantId = auth()->user()->tenant_id;

            if ((int)$tenantId !== $userTenantId) {
                return response()->json(['error' => 'Unauthorized access to this tenant'], 403);
            }
        }

        return $next($request);
    }
}
