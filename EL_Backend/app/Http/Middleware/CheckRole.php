<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Bạn cần đăng nhập để tiếp tục.',
            ], 401);
        }

        // Hỗ trợ truyền nhiều role dạng role:admin,user
        $roles = array_values(array_filter(array_map('trim', explode(',', $role))));

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Bạn không có quyền truy cập khu vực này.'
        ], 403);
    }
}
