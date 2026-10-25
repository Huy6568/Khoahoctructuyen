<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
	use WithoutModelEvents;

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$categories = [
			['name' => 'Lập trình', 'status' => 'accepted'],
			['name' => 'Phát triển web', 'status' => 'accepted'],
			['name' => 'Phát triển mobile', 'status' => 'accepted'],
			['name' => 'Khoa học dữ liệu', 'status' => 'accepted'],
			['name' => 'Trí tuệ nhân tạo', 'status' => 'accepted'],
			['name' => 'An ninh mạng', 'status' => 'accepted'],
			['name' => 'Thiết kế', 'status' => 'accepted'],
			['name' => 'Kinh doanh', 'status' => 'accepted'],
			['name' => 'Marketing', 'status' => 'accepted'],
			['name' => 'Ngoại ngữ', 'status' => 'accepted'],
			['name' => 'Phát triển cá nhân', 'status' => 'accepted'],
			['name' => 'Tài chính', 'status' => 'accepted'],
			['name' => 'Năng suất làm việc', 'status' => 'accepted'],
		];

		foreach ($categories as $category) {
			Category::updateOrCreate(
				['name' => $category['name']],
				$category
			);
		}
	}
}
