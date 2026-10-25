<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    // API 1: Upload Ảnh (Thumbnail khóa học)
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Tối đa 5MB
        ]);

        if ($request->hasFile('file')) {
            // Lưu vào thư mục storage/app/public/thumbnails
            $path = $request->file('file')->store('thumbnails', 'public');

            return response()->json([
                'message' => 'Tải ảnh thành công!',
                'url' => asset('storage/' . $path) // Trả về đường dẫn tuyệt đối (http://localhost:8000/storage/...)
            ]);
        }
        return response()->json(['message' => 'Lỗi upload ảnh'], 400);
    }

    // API 2: Upload Video (Nội dung bài học)
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:mp4,webm|max:102400', // Tối đa 100MB
        ]);

        if ($request->hasFile('file')) {
            // Lưu vào thư mục storage/app/public/videos
            $path = $request->file('file')->store('videos', 'public');

            return response()->json([
                'message' => 'Tải video thành công!',
                'url' => asset('storage/' . $path)
            ]);
        }
        return response()->json(['message' => 'Lỗi upload video'], 400);
    }
}
