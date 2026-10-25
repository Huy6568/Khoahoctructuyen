<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import api from '../../api/axios'

type NotificationItem = {
  id: number
  read_at: string | null
  data: {
    title: string
    message: string
  }
}

const notifications = ref<NotificationItem[]>([])
const unreadCount = ref(0)
const isOpen = ref(false) // Đóng mở hộp thoại
const notificationRef = ref<HTMLElement | null>(null)

const handleDocumentClick = (event: MouseEvent) => {
  if (!isOpen.value) return

  const target = event.target as Node | null
  if (notificationRef.value && target && !notificationRef.value.contains(target)) {
    isOpen.value = false
  }
}

const fetchNotifications = async () => {
  const token = localStorage.getItem('access_token')
  if (!token) {
    notifications.value = []
    unreadCount.value = 0
    return
  }

    try {
        const res = await api.get('/notifications')
        notifications.value = res.data.notifications
        unreadCount.value = res.data.unread_count
    } catch (error) {
        console.error(error)
    }
}

const markAsRead = async (notif: NotificationItem) => {
    if (notif.read_at !== null) return // Đã đọc rồi thì bỏ qua

    try {
        await api.patch(`/notifications/${notif.id}/read`)
        notif.read_at = new Date().toISOString() // Cập nhật UI ngay lập tức
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
        console.error(error)
    }
}

// Gọi ngay khi load trang
onMounted(() => {
    fetchNotifications()
    document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
  <div ref="notificationRef" class="notification-wrapper">
    <button class="bell-btn" @click="isOpen = !isOpen">
      <i class="bi bi-bell"></i>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>
    <div v-if="isOpen" class="notif-dropdown-menu">
      <div class="dropdown-header">Thông báo</div>
      
      <div v-if="notifications.length === 0" class="p-3 text-center text-muted" style="font-size: 15px;">
        Không có thông báo nào.
      </div>

      <div 
        v-for="notif in notifications" 
        :key="notif.id"
        class="notif-item"
        :class="{ 'unread': notif.read_at === null }"
        @click="markAsRead(notif)"
      >
        <div class="notif-title">{{ notif.data.title }}</div>
        <div class="notif-message">{{ notif.data.message }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notification-wrapper { position: relative; display: inline-flex; align-items: center; }
.bell-btn { position: relative; font-size: 20px; background: none; border: none; cursor: pointer; color: #1c1d1f; padding: 0; line-height: 1; transition: color 0.2s; }
.bell-btn:hover { color: #a435f0; }
.badge { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; font-size: 10px; padding: 2px 5px; }
.notif-dropdown-menu { 
    /* Đổi từ absolute sang fixed để không bị thẻ cha "giam giữ" */
    position: fixed; 
    
    /* Bạn có thể tự chỉnh 2 con số này cho vừa với Navbar của bạn */
    top: 70px; 
    right: 20px; 
    
    width: 300px; 
    background-color: #ffffff; 
    border: 1px solid #e5e7eb; 
    border-radius: 8px; 
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); 
    
    /* Đẩy lên level cao nhất có thể */
    z-index: 999999; 
    
    max-height: 400px; 
    overflow-y: auto; 
    color: #000; /* Đảm bảo chữ màu đen để không bị lẫn với nền trắng */
}
.dropdown-header { padding: 10px; font-weight: bold; border-bottom: 1px solid #eee; }
.notif-item { padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s; }
.notif-item:hover { background: #f9f9f9; }
.notif-item.unread { background: #e0f2fe; /* Màu xanh nhạt cho thông báo chưa đọc */ }
.notif-title { font-weight: bold; font-size: 14px; }
.notif-message { font-size: 13px; color: #555; margin-top: 4px; }
</style>