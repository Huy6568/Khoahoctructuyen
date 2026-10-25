<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'fullname' => 'System Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'bio' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Nguyen Van Son',
                'email' => 'teacher@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Chuyên gia lập trình Laravel với 10 năm kinh nghiệm.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Nguyen Van A',
                'email' => 'student@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Tran Thi B',
                'email' => 'tranthib@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Yêu thích học thiết kế và giao diện web.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Le Minh C',
                'email' => 'leminhc@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Quan tâm đến dữ liệu và phân tích sản phẩm.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'banned',
            ],
            [
                'fullname' => 'Pham Van D',
                'email' => 'phamvand@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Thường xuyên học các khóa lập trình backend.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Hoang Mai E',
                'email' => 'hoangmaie@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Đang tìm hiểu marketing và quản lý dự án.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
            [
                'fullname' => 'Vu Quang F',
                'email' => 'vuquangf@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'bio' => 'Quan tâm đến tự động hóa và Python.',
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'active',
            ],
        ]);
    }
}
