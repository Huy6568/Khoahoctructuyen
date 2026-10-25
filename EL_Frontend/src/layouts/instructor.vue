<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import NotificationPopup from '../components/common/NotificationPopup.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isUserMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement | null>(null)

const handleDocumentClick = (event: MouseEvent) => {
  if (!isUserMenuOpen.value) return

  const target = event.target as Node | null
  if (userMenuRef.value && target && !userMenuRef.value.contains(target)) {
    isUserMenuOpen.value = false
  }
}

const ensureAuthenticated = async () => {
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (!authStore.isAuthenticated) {
    router.replace('/')
  }
}

const navbarTitle = computed(() => {
  const titleFromMeta = route.meta?.title
  if (typeof titleFromMeta === 'string' && titleFromMeta.trim()) {
    return titleFromMeta
  }

  if (route.path.startsWith('/instructor/courses/curriculum')) {
    return 'Chương trình giảng dạy'
  }

  if (route.path.startsWith('/instructor/courses/builder')) {
    return 'Tạo khóa học'
  }

  if (/^\/instructor\/courses\/builder\/\d+$/.test(route.path)) {
      return 'Sửa khóa học';
  }

  if (route.path.startsWith('/instructor/courses')) {
    return 'Khóa học'
  }

  return ''
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  ensureAuthenticated()
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})

watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (!isAuthenticated) {
      router.replace('/login')
    }
  }
)
</script>

<template>
  <div class="dashboard-container">
    <aside class="sidebar">
      <router-link to="/instructor/statistics" class="nav-item">
        <i class="bi bi-bar-chart-line-fill"></i>
        <span class="nav-title">Thống kê</span>
      </router-link>
      <router-link to="/instructor/courses" class="nav-item">
        <i class="bi bi-collection-play-fill"></i>
        <span class="nav-title">Khóa học của tôi</span>
      </router-link>
      <!-- <router-link to="/instructor/messages" class="nav-item">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="nav-title">Giao tiếp (WIP)</span>
      </router-link> -->
    </aside>

    <div class="main-wrapper">
      <header class="navbar">
        <div class="navbar-brand">
          <h2>{{ navbarTitle }}</h2>
        </div>
        <div class="navbar-actions">
          <router-link to="/" class="btn-switch-role">Học viên</router-link>
          
          <div class="notification-icon">
            <NotificationPopup />
          </div>

          <div v-if="authStore.isAuthenticated" ref="userMenuRef" class="user-menu">
            <div class="user-menu-toggle" @click="isUserMenuOpen = !isUserMenuOpen">
              <img :src="authStore.user?.avatar_url || `https://ui-avatars.com/api/?name=${authStore.user?.name}`" alt="Avatar" class="avatar">
            </div>
            
            <div v-if="isUserMenuOpen" class="user-menu-dropdown">
              <div class="user-menu-header">
                <div class="user-menu-name">{{ authStore.user?.fullname }}</div>
              </div>
              <router-link to="/instructor/profile" class="user-menu-item">Thông tin cá nhân</router-link>
              <router-link to="/mylearning" class="user-menu-item">Khóa học</router-link>
              <button @click="handleLogout" class="user-menu-item user-menu-logout w-100 text-start">Đăng xuất</button>
            </div>
          </div>
        </div>
      </header>

      <main class="content-area">
        <router-view></router-view>
      </main>

    </div>
  </div>

</template>

<style scoped>
/* Reset & Variables */
:root {
  --primary-color: #a435f0;
  --bg-color: #f7f9fa;
  --text-color: #1c1d1f;
  --border-color: #d1d7dc;
  --sidebar-width: 70px;
}

.dashboard-container { display: flex; height: 100vh; background-color: #f7f9fa; color: #1c1d1f;}

/* Sidebar */
.sidebar { width: 70px; background-color: #1c1d1f; display: flex; flex-direction: column; padding-top: 20px; z-index: 10; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
.sidebar:hover { width: 240px; }
.nav-item { display: flex; align-items: center; width: 240px; padding: 16px 25px; color: #fff; text-decoration: none; transition: color 0.2s ease, background-color 0.2s ease; cursor: pointer; }
.nav-item i { display: inline-flex; align-items: center; justify-content: center; width: 20px; min-width: 20px; flex-shrink: 0; font-size: 20px; text-align: center; }
.nav-item:hover, .nav-item.router-link-active { color: #a435f0; background-color: rgba(255, 255, 255, 0.05); }
.nav-title { margin-left: 20px; font-size: 15px; font-weight: 500; white-space: nowrap; opacity: 0; transform: translateX(-10px); transition: opacity 0.3s ease, transform 0.3s ease; }
.sidebar:hover .nav-title { opacity: 1; transform: translateX(0); }

/* Main Wrapper & Navbar */
.main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.navbar { height: 70px; background-color: #fff; border-bottom: 1px solid #d1d7dc; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; flex-shrink: 0;}
.navbar-brand h2 { font-size: 20px; font-weight: 600; margin: 0; }
.navbar-actions { display: flex; align-items: center; gap: 24px; }
.btn-switch-role { text-decoration: none; color: #1c1d1f; font-weight: 500; padding: 8px 0; }
.btn-switch-role:hover { color: #a435f0; }
.notification-icon { font-size: 20px; cursor: pointer; color: #1c1d1f; }
.notification-icon:hover { color: #a435f0; }

/* User Menu */
.avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer; }
.user-menu { position: relative; display: flex; align-items: center; }
.user-menu-toggle { cursor: pointer; line-height: 0; }
.user-menu-dropdown { position: absolute; top: calc(100% + 12px); right: 0; width: 220px; background: #fff; border: 1px solid #d1d7dc; border-radius: 14px; box-shadow: 0 16px 32px rgba(0, 0, 0, 0.12); padding: 10px; z-index: 30; }
.user-menu-header { padding: 6px 8px 10px; border-bottom: 1px solid #d1d7dc; margin-bottom: 8px; }
.user-menu-name { font-weight: 700; font-size: 14px; color: #1c1d1f; margin-bottom: 2px; }
.user-menu-item { display: block; width: 100%; border: 0; background: transparent; text-align: left; text-decoration: none; color: #1c1d1f; padding: 10px 8px; border-radius: 10px; font-size: 14px; cursor: pointer; transition: all 0.2s;}
.user-menu-item:hover { background: #f7f4ff; color: #a435f0; }
.user-menu-logout { color: #c62828; }
.user-menu-logout:hover { background: rgba(198, 40, 40, 0.08); color: #c62828; }

/* Content Area */
.content-area { padding: 40px; overflow-y: auto; flex: 1; }
</style>