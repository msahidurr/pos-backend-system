<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant check for register route
        if ($request->is('api/register') || $request->is('api/login')) {
            return $next($request);
        }

        // Get tenant ID from X-Tenant-ID header
        $tenantId = $request->header('X-Tenant-ID');
        
        if (!$tenantId) {
            return response()->json(['error' => 'X-Tenant-ID header is required'], 400);
        }

        // Verify tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Invalid tenant ID'], 404);
        }

        // Store tenant in request for use in controllers
        $request->attributes->set('tenant', $tenant);
        
        return $next($request);
    }
}
