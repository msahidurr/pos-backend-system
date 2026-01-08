<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    public function handle(Request $request, Closure $next)
    {
        // Sanitize string inputs to prevent XSS
        $input = $request->all();
        
        array_walk_recursive($input, function (&$item) {
            if (is_string($item)) {
                $item = strip_tags($item);
                $item = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            }
        });

        $request->replace($input);

        return $next($request);
    }
}
