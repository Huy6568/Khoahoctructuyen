<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Repositories\CheckoutRepository;
use Illuminate\Support\Arr;
use App\Notifications\SystemNotification;

class CheckoutService
{
    public function __construct(
        protected CheckoutRepository $checkoutRepository
    ) {
    }

    public function prepareCheckout(int $courseId, int $userId): array
    {
        $course = $this->checkoutRepository->findCourseById($courseId);

        if ((float) $course->price <= 0) {
            $this->checkoutRepository->upsertEnrollment($userId, $course->id, 0);
            $this->notifyEnrollmentSuccess($userId, $course);

            return [
                'type' => 'free_enrolled',
                'course' => $course,
                'message' => 'Khóa học miễn phí đã được đăng ký thành công.',
            ];
        }

        return [
            'type' => 'payment_required',
            'course' => $course,
            'amount' => (float) $course->price,
        ];
    }

    public function createPayment(array $validated, int $userId, string $ipAddress): array
    {
        $course = $this->checkoutRepository->findCourseById((int) $validated['course_id']);
        $amount = (float) $course->price;

        if ($amount <= 0) {
            $this->checkoutRepository->upsertEnrollment($userId, $course->id, 0);
            $this->notifyEnrollmentSuccess($userId, $course);

            return [
                'type' => 'free_enrolled',
                'course' => $course,
                'message' => 'Khóa học miễn phí đã được đăng ký thành công.',
            ];
        }

        if ($validated['payment_method'] !== 'vnpay') {
            abort(422, 'Hiện tại chỉ hỗ trợ phương thức thanh toán VNPAY.');
        }

        return [
            'type' => 'redirect',
            'payment_url' => $this->buildVnpayUrl($course, $userId, $amount, $ipAddress),
            'course' => $course,
            'amount' => $amount,
        ];
    }

    protected function buildVnpayUrl(Course $course, int $userId, float $amount, string $ipAddress): string
    {
        $vnpTmnCode = env('VNP_TMN_CODE');
        $vnpHashSecret = env('VNP_HASH_SECRET');
        $vnpUrl = env('VNP_URL');
        $vnpReturnUrl = env('VNP_RETURN_URL');

        abort_if(!$vnpTmnCode || !$vnpHashSecret || !$vnpUrl || !$vnpReturnUrl, 500, 'Thiếu cấu hình VNPAY.');

        $orderId = (string) now()->timestamp;
        $orderInfo = "course_id={$course->id}|user_id={$userId}";

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnpTmnCode,
            'vnp_Amount' => (int) round($amount * 100),
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddress,
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => $vnpReturnUrl,
            'vnp_TxnRef' => $orderId,
        ];

        ksort($inputData);

        $query = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $hashData = $query;
        $secureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);

        return rtrim($vnpUrl, '?') . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    public function handleVnpayReturn(array $queryParams): array
    {
        $validation = $this->validateVnpayPayload($queryParams);

        if (!$validation['success']) {
            return $validation;
        }

        if (($queryParams['vnp_ResponseCode'] ?? null) !== '00') {
            return [
                'success' => false,
                'message' => 'Giao dịch không thành công hoặc đã bị hủy.',
            ];
        }

        $orderInfo = $queryParams['vnp_OrderInfo'] ?? '';
        preg_match('/course_id=(\d+)\|user_id=(\d+)/', (string) $orderInfo, $matches);
        abort_if(count($matches) < 3, 422, 'Không đọc được thông tin đơn hàng.');

        $courseId = (int) $matches[1];
        $userId = (int) $matches[2];
        $amount = isset($queryParams['vnp_Amount']) ? ((float) $queryParams['vnp_Amount'] / 100) : 0;

        $enrollment = $this->checkoutRepository->upsertEnrollment($userId, $courseId, $amount);
        $course = $this->checkoutRepository->findCourseById($courseId);
    $this->notifyEnrollmentSuccess($userId, $course);

        return [
            'success' => true,
            'message' => 'Thanh toán thành công! Chúc bạn học tốt.',
            'data' => [
                'course_id' => $enrollment->course_id,
                'user_id' => $enrollment->user_id,
                'purchase_price' => (float) $enrollment->purchase_price,
                'purchased_at' => optional($enrollment->purchased_at)?->toISOString(),
            ],
        ];
    }

    public function handleVnpayIpn(array $queryParams): array
    {
        $validation = $this->validateVnpayPayload($queryParams);

        if (!$validation['success']) {
            return [
                'RspCode' => $validation['RspCode'],
                'Message' => $validation['Message'],
            ];
        }

        if (($queryParams['vnp_ResponseCode'] ?? null) !== '00') {
            return [
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ];
        }

        $orderInfo = $queryParams['vnp_OrderInfo'] ?? '';
        preg_match('/course_id=(\d+)\|user_id=(\d+)/', (string) $orderInfo, $matches);

        if (count($matches) < 3) {
            return [
                'RspCode' => '99',
                'Message' => 'Invalid order info',
            ];
        }

        $courseId = (int) $matches[1];
        $userId = (int) $matches[2];
        $amount = isset($queryParams['vnp_Amount']) ? ((float) $queryParams['vnp_Amount'] / 100) : 0;

        $this->checkoutRepository->upsertEnrollment($userId, $courseId, $amount);
        $course = $this->checkoutRepository->findCourseById($courseId);
        $this->notifyEnrollmentSuccess($userId, $course);

        return [
            'RspCode' => '00',
            'Message' => 'Confirm Success',
        ];
    }

    protected function notifyEnrollmentSuccess(int $userId, Course $course): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        $alreadyNotified = $user->notifications()
            ->where('type', SystemNotification::class)
            ->where('data->title', 'Thanh toán thành công')
            ->where('data->action_url', '/my-learning')
            ->where('data->meta->course_id', $course->id)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $user->notify(new SystemNotification(
            'Thanh toán thành công',
            'Bạn đã sở hữu khóa học "'.$course->title.'".',
            '/my-learning',
            [
                'course_id' => $course->id,
            ]
        ));
    }

    protected function validateVnpayPayload(array $queryParams): array
    {
        $vnpHashSecret = env('VNP_HASH_SECRET');
        abort_if(!$vnpHashSecret, 500, 'Thiếu cấu hình VNPAY.');

        $inputData = Arr::where($queryParams, function ($value, $key) {
            return str_starts_with($key, 'vnp_');
        });

        $receivedHash = $inputData['vnp_SecureHash'] ?? null;

        if (!$receivedHash) {
            return [
                'success' => false,
                'RspCode' => '97',
                'Message' => 'Missing signature',
            ];
        }

        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $computedHash = hash_hmac('sha512', $hashData, $vnpHashSecret);

        if (!hash_equals($computedHash, $receivedHash)) {
            return [
                'success' => false,
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ];
        }

        return [
            'success' => true,
        ];
    }
}
