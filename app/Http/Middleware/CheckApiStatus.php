<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckApiStatus
{
    public function handle(Request $request, Closure $next)
    {
        $status = DB::table('system_settings')
            ->where('key', 'api_status')
            ->value('value') ?? 'offline';

        if ($status !== 'online') {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Service is currently unavailable.',
            ], 403);
        }

        return $next($request);
    }
}
