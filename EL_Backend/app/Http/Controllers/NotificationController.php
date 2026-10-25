<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Lấy danh sách thông báo
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            // Lấy 10 thông báo mới nhất (cả đã đọc và chưa đọc)
            'notifications' => $user->notifications()->take(10)->get(),
            // Đếm số lượng chưa đọc để hiện chấm đỏ
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    // Đánh dấu 1 thông báo là đã đọc
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Đã đánh dấu đọc']);
    }

    // Đánh dấu TẤT CẢ là đã đọc
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Đã đọc tất cả']);
    }
}
