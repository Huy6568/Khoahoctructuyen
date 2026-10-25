<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonCompletion extends Model
{
    // Chỉ định chính xác tên bảng trong Database
    protected $table = 'lesson_completions';

    // Cho phép Laravel thêm dữ liệu hàng loạt vào 2 cột này
    protected $fillable = [
        'user_id',
        'lesson_id'
    ];
}
