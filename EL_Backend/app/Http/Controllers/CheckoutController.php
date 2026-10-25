<?php

namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\CourseResource;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
    }

    public function vnpayReturn(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $result = $this->checkoutService->handleVnpayReturn($request->query());

        if (!($result['success'] ?? false)) {
            $query = http_build_query([
                'message' => $result['message'] ?? 'Thanh toán không thành công.',
                'code' => $result['RspCode'] ?? null,
            ]);

            return redirect()->away($frontendUrl . '/payment-failed' . ($query ? '?' . $query : ''));
        }

        $data = $result['data'] ?? [];
        $query = http_build_query([
            'course_id' => $data['course_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'price' => $data['purchase_price'] ?? null,
            'at' => $data['purchased_at'] ?? null,
        ]);

        return redirect()->away($frontendUrl . '/payment-success' . ($query ? '?' . $query : ''));
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $courseId = $request->integer('course_id');

        if (!$courseId) {
            return response()->json([
                'message' => 'Vui lòng chọn khóa học trước khi thanh toán.',
            ], 422);
        }

        $result = $this->checkoutService->prepareCheckout($courseId, auth()->id());

        if ($result['type'] === 'free_enrolled') {
            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'course' => new CourseResource($result['course']),
                    'enrolled' => true,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'course' => new CourseResource($result['course']),
                'amount' => $result['amount'],
                'payment_required' => true,
            ],
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'payment_method' => ['required', 'in:vnpay,paypal'],
        ]);

        $result = $this->checkoutService->createPayment($validated, auth()->id(), $request->ip());

        if ($result['type'] === 'free_enrolled') {
            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'course' => new CourseResource($result['course']),
                    'enrolled' => true,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Khởi tạo thanh toán thành công.',
            'data' => [
                'payment_url' => $result['payment_url'],
                'course' => new CourseResource($result['course']),
                'amount' => $result['amount'],
                'payment_method' => $validated['payment_method'],
            ],
        ]);
    }

    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = $this->checkoutService->handleVnpayIpn($request->query());

        return response()->json($result);
    }
}
