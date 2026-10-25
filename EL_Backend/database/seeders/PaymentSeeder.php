<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::query()
            ->where('role', 'user')
            ->where('email', '!=', 'teacher@gmail.com')
            ->orderBy('id')
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        $courses = Course::query()
            ->where('status', 'published')
            ->orderBy('id')
            ->take(10)
            ->get();

        if ($courses->isEmpty()) {
            return;
        }

        $baseDate = now()->subMonths(4)->startOfMonth();

        foreach ($students as $studentIndex => $student) {
            $start = $studentIndex % 3;
            $studentCourses = $courses->slice($start, 4);

            foreach ($studentCourses as $courseIndex => $course) {
                $purchasedAt = $baseDate->copy()->addDays(($studentIndex * 3) + ($courseIndex * 5) + 2);
                $status = 'completed';
                $amount = (int) round((float) $course->price);

                // Tạo thêm một ít giao dịch failed để dữ liệu payment dashboard thực tế hơn.
                if ((($studentIndex + $courseIndex) % 7) === 0) {
                    $status = 'failed';
                    $amount = 0;
                }

                $payment = Payment::create([
                    'user_id' => $student->id,
                    'order_id' => 'ORD_' . now()->format('ymd') . '_' . Str::upper(Str::random(10)),
                    'vnpay_transaction_id' => $status === 'completed' ? 'VNP' . rand(100000, 999999) : null,
                    'amount' => $amount,
                    'status' => $status,
                    'payment_method' => 'vnpay',
                    'raw_response' => [
                        'course_id' => $course->id,
                        'user_id' => $student->id,
                        'status' => $status,
                    ],
                    'created_at' => $purchasedAt,
                    'updated_at' => $purchasedAt,
                ]);

                if ($status === 'completed') {
                    Enrollment::updateOrCreate(
                        [
                            'user_id' => $student->id,
                            'course_id' => $course->id,
                        ],
                        [
                            'payment_id' => $payment->id,
                            'purchase_price' => (float) $course->price,
                            'purchased_at' => $purchasedAt,
                        ]
                    );
                }
            }
        }
    }
}
