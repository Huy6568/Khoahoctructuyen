<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy danh sách ID của tất cả khóa học đã tạo từ CourseSeeder
        $courseIds = DB::table('courses')->pluck('id');

        foreach ($courseIds as $courseId) {
            // 2. Tạo một Section (Chương) mặc định cho mỗi khóa học
            $sectionId = DB::table('sections')->insertGetId([
                'course_id' => $courseId,
                'title' => 'Chương 1: Giới thiệu và Kiến thức nền tảng',
                'order_index' => 1,
            ]);

            // 3. Tạo các bài học (Lessons) mẫu cho chương này
            DB::table('lessons')->insert([
                [
                    'section_id' => $sectionId,
                    'title' => 'Video hướng dẫn: Bắt đầu lộ trình học tập',
                    'content_type' => 'video',
                    'video_url' => 'https://player.vimeo.com/video/1112184019?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479;&title=0&byline=0&portrait=0',
                    'article_content' => null,
                    'order_index' => 1,
                ],
                [
                    'section_id' => $sectionId,
                    'title' => 'Bài đọc: Tổng quan về nội dung khóa học',
                    'content_type' => 'article',
                    'video_url' => null,
                    'article_content' => 'Chào mừng bạn đến với khóa học. Trong bài học này, chúng ta sẽ cùng điểm qua các mục tiêu chính và các công cụ cần thiết để hoàn thành khóa học một cách tốt nhất...',
                    'order_index' => 2,
                ]
            ]);
        }
    }
}
