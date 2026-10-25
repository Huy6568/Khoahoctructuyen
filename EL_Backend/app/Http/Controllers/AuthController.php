<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $result = $this->authService->register($validated);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'data' => $result,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->login($validated['email'], $validated['password']);

        if (!$result) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác.'
            ], 401);
        }

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'data' => $result,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Đã đăng xuất thành công'
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        // Trả về thông tin user đang đăng nhập
        return response()->json([
            'data' => $request->user()
        ]);
    }
}
