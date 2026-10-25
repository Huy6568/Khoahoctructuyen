<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $actionUrl;
    protected array $meta;

    public function __construct($title, $message, $actionUrl = null, array $meta = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->meta = $meta;
    }

    // Xác định kênh gửi là Database
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Đóng gói dữ liệu thành JSON để lưu vào cột `data`
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'meta' => $this->meta,
        ];
    }
}
