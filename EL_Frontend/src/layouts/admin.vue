<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import NotificationPopup from '../components/common/NotificationPopup.vue'

const router = useRouter()
const route = useRoute()
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
      <router-link to="/admin/statistics" class="nav-item">
        <i class="bi bi-pie-chart-fill"></i>
        <span class="nav-title">Thống kê</span>
      </router-link>
      <router-link to="/admin/users" class="nav-item">
        <i class="bi bi-people-fill"></i>
        <span class="nav-title">Người dùng</span>
      </router-link>
      <router-link to="/admin/courses" class="nav-item">
        <i class="bi bi-collection-play-fill"></i>
        <span class="nav-title">Khóa học</span>
      </router-link>
      <router-link to="/admin/categories" class="nav-item">
        <i class="bi bi-tags-fill"></i>
        <span class="nav-title">Danh mục</span>
      </router-link>
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
              <router-link to="/admin/profile" class="user-menu-item">Thông tin cá nhân</router-link>
              <router-link to="/mylearning" class="user-menu-item">Khóa học</router-link>
              <button @click="handleLogout" class="user-menu-item user-menu-logout w-100 text-start">Đăng xuất</button>
            </div>
          </div>
        </div>
      </header>

      <main class="content-area">
        <slot />
      </main>

    </div>
  </div>
</template>

<style scoped>
/* Reset & Variables (Dùng chung bộ khung của Instructor) */
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

<style>
/* === ADMIN CONTAINER === */
.admin-container { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); padding: 24px; border: 1px solid #e5e7eb; }

/* === TOOLBAR === */
.admin-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
.search-form { display: flex; flex: 1; max-width: 400px; }
.form-control { padding: 10px 16px; border: 1px solid #d1d5db; outline: none; font-size: 14px; border-radius: 6px; transition: border-color 0.2s; }
.search-form input { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; width: 100%; }
.search-form input:focus, .form-select:focus { border-color: #6366f1; }
.btn-search { background-color: #f3f4f6; border: 1px solid #d1d5db; border-left: none; padding: 0 16px; border-top-right-radius: 6px; border-bottom-right-radius: 6px; cursor: pointer; color: #4b5563; }
.btn-search:hover { background-color: #e5e7eb; }
.form-select { min-width: 180px; cursor: pointer; background-color: #fff; }

/* === TABLE === */
.table-responsive { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.admin-table th, .admin-table td { padding: 16px; border-bottom: 1px solid #e5e7eb; text-align: left; }
.admin-table th { background-color: #f9fafb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
.admin-table tbody tr:hover { background-color: #f9fafb; }
.text-center { text-align: center !important; }
.text-muted { color: #6b7280; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.d-inline { display: inline-block; }

/* === BADGES (STATUS) === */
.badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
.badge-primary { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-success { background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.badge-danger  { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-secondary { background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }

/* === ACTION BUTTONS === */
.action-buttons { display: flex; justify-content: center; gap: 8px; }
.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 16px; transition: all 0.2s; background-color: #fff; border: 1px solid #e5e7eb; }
.btn-edit { color: #4f46e5; }
.btn-edit:hover { background-color: #e0e7ff; border-color: #c7d2fe; }
.btn-accept { color: #16a34a; }
.btn-accept:hover { background-color: #dcfce7; border-color: #bbf7d0; }
.btn-reject { color: #dc2626; }
.btn-reject:hover { background-color: #fee2e2; border-color: #fecaca; }
</style>