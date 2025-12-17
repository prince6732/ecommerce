<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->status) {
            $request->user()->tokens()->delete();

            return response()->json([
                'res' => 'error',
                'message' => 'Your account has been blocked. Please contact administrator.',
            ], 403);
        }

        return $next($request);
    }
}
