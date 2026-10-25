<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanUnusedFiles extends Command
{
    // Tên lệnh để chạy trong Terminal
    protected $signature = 'cleanup:unused-files';
    protected $description = 'Xóa các ảnh/video mồ côi không được lưu trong DB sau 24h';

    public function handle()
    {
        $this->info('Bắt đầu dọn dẹp file rác...');
        $deletedCount = 0;

        // 1. Quét thư mục Videos
        $videos = Storage::disk('public')->files('videos');
        foreach ($videos as $video) {
            $this->processFile($video, 'lessons', 'video_url', $deletedCount);
        }

        // 2. Quét thư mục Thumbnails
        $thumbnails = Storage::disk('public')->files('thumbnails');
        foreach ($thumbnails as $thumbnail) {
            $this->processFile($thumbnail, 'courses', 'thumbnail_url', $deletedCount);
        }

        $this->info("Hoàn tất! Đã xóa {$deletedCount} file rác.");
    }

    private function processFile($filePath, $tableName, $columnName, &$deletedCount)
    {
        // Lấy thời gian file được tạo/sửa đổi lần cuối
        $lastModified = Carbon::createFromTimestamp(Storage::disk('public')->lastModified($filePath));

        if (now()->diffInHours($lastModified) >= 2) {
            return;
        }

        // Lấy tên file (VD: file_abc.mp4)
        $fileName = basename($filePath);

        // Kiểm tra xem Database có dòng nào chứa tên file này không
        $isUsedInDb = DB::table($tableName)->where($columnName, 'LIKE', '%' . $fileName . '%')->exists();

        if (!$isUsedInDb) {
            Storage::disk('public')->delete($filePath);
            $this->line("Đã xóa rác: {$fileName}");
            $deletedCount++;
        }
    }
}
