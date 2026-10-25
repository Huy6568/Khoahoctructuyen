<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = DB::table('users')
            ->where('role', 'user')
            ->where('email', '!=', 'teacher@gmail.com')
            ->pluck('id')
            ->values();

        $teacherId = DB::table('users')->where('email', 'teacher@gmail.com')->value('id');
        $courses = DB::table('courses')->orderBy('id')->pluck('id')->values();
        $lessons = DB::table('lessons')->orderBy('id')->pluck('id')->values();

        if ($students->isEmpty() || $courses->isEmpty()) {
            return;
        }

        // Mỗi học viên đăng ký tối đa 4 khóa đầu để tạo dữ liệu học tập phong phú.
        foreach ($students as $studentIndex => $studentId) {
            $targetCourses = $courses->slice($studentIndex % 3, 4);

            foreach ($targetCourses as $offset => $courseId) {
                $purchasedAt = now()->subDays(($studentIndex * 3) + $offset + 1);
                $coursePrice = (float) (DB::table('courses')->where('id', $courseId)->value('price') ?? 0);

                DB::table('enrollments')->updateOrInsert(
                    [
                        'user_id' => $studentId,
                        'course_id' => $courseId,
                    ],
                    [
                        'payment_id' => null,
                        'purchase_price' => $coursePrice,
                        'purchased_at' => $purchasedAt,
                    ]
                );
            }
        }

        $reviewTemplates = [
            'Nội dung dễ hiểu, học xong áp dụng được ngay.',
            'Bài giảng rõ ràng, ví dụ thực tế.',
            'Khóa học chất lượng tốt, đáng học.',
            'Tốc độ bài giảng vừa phải, phù hợp người mới.',
            'Phần thực hành rất hữu ích.',
        ];

        foreach ($students as $studentIndex => $studentId) {
            $courseId = $courses[$studentIndex % max(1, $courses->count())];

            DB::table('reviews')->updateOrInsert(
                [
                    'user_id' => $studentId,
                    'course_id' => $courseId,
                ],
                [
                    'rating' => 4 + ($studentIndex % 2),
                    'comment' => $reviewTemplates[$studentIndex % count($reviewTemplates)],
                    'created_at' => now()->subDays($studentIndex + 1),
                    'updated_at' => now()->subDays($studentIndex + 1),
                ]
            );
        }

        if ($lessons->isNotEmpty()) {
            $questions = [
                'Phần cài đặt môi trường ở bài này có cần phiên bản mới nhất không?',
                'Nếu gặp lỗi dependency thì nên xử lý theo thứ tự nào?',
                'Có tài liệu nào để đọc thêm sau bài học này không?',
                'Em nên luyện tập thêm bài nào để nắm chắc kiến thức?',
                'Có thể áp dụng nội dung này cho dự án thực tế như thế nào?',
            ];

            foreach ($students as $studentIndex => $studentId) {
                $lessonId = $lessons[$studentIndex % max(1, $lessons->count())];
                $createdAt = now()->subDays($studentIndex + 2);

                $questionId = DB::table('questions')->insertGetId([
                    'lesson_id' => $lessonId,
                    'user_id' => $studentId,
                    'content' => $questions[$studentIndex % count($questions)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if ($teacherId) {
                    DB::table('answers')->insert([
                        'question_id' => $questionId,
                        'user_id' => $teacherId,
                        'content' => 'Em kiểm tra lại các bước theo thứ tự trong bài, nếu vẫn lỗi thì gửi ảnh log để giảng viên hỗ trợ tiếp.',
                        'created_at' => $createdAt->copy()->addHour(),
                        'updated_at' => $createdAt->copy()->addHour(),
                    ]);
                }
            }
        }
    }
}
