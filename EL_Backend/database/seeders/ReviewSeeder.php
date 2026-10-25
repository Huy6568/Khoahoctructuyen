<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
	use WithoutModelEvents;

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$reviews = [
			['user_id' => 3, 'course_id' => 1, 'rating' => 5, 'comment' => 'Khóa học rất rõ ràng, dễ hiểu và thực tế.'],
            ['user_id' => 4, 'course_id' => 1, 'rating' => 4, 'comment' => 'Nội dung đẹp, phù hợp cho người mới bắt đầu.'],
			['user_id' => 4, 'course_id' => 2, 'rating' => 4, 'comment' => 'Nội dung đẹp, phù hợp cho người mới bắt đầu.'],
			['user_id' => 5, 'course_id' => 3, 'rating' => 5, 'comment' => 'Phần dữ liệu được giải thích rất chi tiết.'],
			['user_id' => 6, 'course_id' => 4, 'rating' => 4, 'comment' => 'API project đi từ cơ bản đến nâng cao khá ổn.'],
			['user_id' => 7, 'course_id' => 5, 'rating' => 5, 'comment' => 'Khóa học quản lý dự án rất hữu ích cho công việc.'],
			['user_id' => 8, 'course_id' => 6, 'rating' => 4, 'comment' => 'Next.js được trình bày dễ theo dõi.'],
			['user_id' => 3, 'course_id' => 7, 'rating' => 5, 'comment' => 'Sanctum và API resources được giải thích dễ hiểu.'],
			['user_id' => 4, 'course_id' => 8, 'rating' => 4, 'comment' => 'SQL nâng cao nhưng vẫn rất dễ tiếp cận.'],
			['user_id' => 5, 'course_id' => 9, 'rating' => 5, 'comment' => 'Design system là phần mình thấy giá trị nhất.'],
			['user_id' => 6, 'course_id' => 10, 'rating' => 4, 'comment' => 'Nội dung marketing cơ bản, phù hợp người mới.'],
			['user_id' => 7, 'course_id' => 11, 'rating' => 5, 'comment' => 'Tiếng Anh cho IT rất sát với nhu cầu thực tế.'],
			['user_id' => 8, 'course_id' => 12, 'rating' => 4, 'comment' => 'Bài học JavaScript ES6+ có cấu trúc tốt.'],
		];

		foreach ($reviews as $review) {
			DB::table('reviews')->updateOrInsert(
				[
					'user_id' => $review['user_id'],
					'course_id' => $review['course_id'],
				],
				[
					'rating' => $review['rating'],
					'comment' => $review['comment'],
					'created_at' => now(),
					'updated_at' => now(),
				]
			);
		}
	}
}
