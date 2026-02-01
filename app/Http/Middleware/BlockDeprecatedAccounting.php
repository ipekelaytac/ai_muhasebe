<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to block deprecated accounting endpoints
 * 
 * This middleware throws an exception for any route that attempts to use
 * the old accounting system (FinanceTransaction, CustomerTransaction, etc.)
 */
class BlockDeprecatedAccounting
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        throw new \Exception(
            'This endpoint is deprecated. The old accounting system has been replaced. ' .
            'Please use the Accounting API at /api/accounting/* instead. ' .
            'Route: ' . $request->path()
        );
    }
}
