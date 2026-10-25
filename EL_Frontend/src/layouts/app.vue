<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import applicationLogo from '../assets/vue.svg'
import AppNavbar from '../components/layouts/AppNavbar.vue'

const router = useRouter()

// Khởi tạo từ token và user info có sẵn
const token = ref(localStorage.getItem('access_token'))
const isAuthenticated = computed(() => !!token.value)

const user = ref<{
  id?: number
  name?: string
  fullname?: string
  email?: string
  role?: string
  avatar?: string
}>({})

const searchQuery = ref('')

// Hàm gọi API lấy thông tin user từ database
const fetchUserProfile = async () => {
  try {
    const response = await api.get('/user/me')
    const userData = response.data?.data ?? response.data ?? {}
    user.value = {
      id: userData.id,
      name: userData.name,
      fullname: userData.fullname || userData.name,
      email: userData.email,
      role: userData.role || 'student',
      avatar: userData.avatar
    }
  } catch (error) {
    console.error('Failed to fetch user profile:', error)
    // Nếu không lấy được từ API, thử lấy từ localStorage
    const savedUser = localStorage.getItem('user_info')
    if (savedUser) {
      try {
        user.value = JSON.parse(savedUser)
      } catch (parseError) {
        console.error('Failed to parse user_info:', parseError)
      }
    }
  }
}

// Khai báo biến để lưu interval ID
let storageCheckInterval: ReturnType<typeof setInterval> | null = null

// Theo dõi token trong localStorage thay đổi trực tiếp (logout từ trang khác, v.v.)
const checkStorageChanges = () => {
  const currentToken = localStorage.getItem('access_token')
  if (currentToken !== token.value) {
    token.value = currentToken
    if (token.value) {
      fetchUserProfile()
    } else {
      user.value = {}
    }
  }
}

// Khởi tạo dữ liệu khi component mount
onMounted(() => {
  // Kiểm tra token
  token.value = localStorage.getItem('access_token')
  
  // Nếu có token, lấy thông tin user
  if (token.value) {
    fetchUserProfile()
  }
  
  // Lắng nghe event storage (khi logout/login ở tab khác)
  window.addEventListener('storage', checkStorageChanges)
  
  // Cũng kiểm tra mỗi 1 giây (cho phép logout từ trang khác cập nhật ngay)
  storageCheckInterval = setInterval(checkStorageChanges, 1000)
})

// Cleanup interval và event listener khi component unmount
onBeforeUnmount(() => {
  if (storageCheckInterval) {
    clearInterval(storageCheckInterval)
  }
  window.removeEventListener('storage', checkStorageChanges)
})

// Xử lý tìm kiếm
const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.push({ path: '/courses', query: { search: searchQuery.value } })
  }
}

// Xử lý đăng xuất
const handleLogout = async () => {
  try {
    // Gọi API đăng xuất tại đây
    await api.post('/logout')
    
    // Xóa ở client
    localStorage.removeItem('access_token')
    localStorage.removeItem('user_info')
    token.value = null
    user.value = {}
    
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    // Vẫn xóa local dù API fail
    localStorage.removeItem('access_token')
    localStorage.removeItem('user_info')
    token.value = null
    user.value = {}
    router.push('/login')
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <AppNavbar
      :is-authenticated="isAuthenticated"
      :user="user"
      :search-query="searchQuery"
      :application-logo="applicationLogo"
      @update:search-query="searchQuery = $event"
      @search="handleSearch"
      @logout="handleLogout"
    />

    <MainLayout>
      <router-view />
    </MainLayout>

    <footer class="text-center py-4 mt-5 border-top">
      <!-- <small class="text-secondary">© 2026 Udemy Clone Project</small> -->
    </footer>
  </div>
</template>



<style scoped>

:root {
  --udemy-purple: #a435f0;
  --udemy-dark: #2d2f31;
  --udemy-gray: #6a6f73;
}

body {
  font-family: Udemy-sans, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: var(--udemy-dark);
}

/* Navbar */
.navbar {
  box-shadow: 0 2px 4px rgba(0,0,0,0.08);
  height: 72px;
}
.search-bar-container {
  flex-grow: 1;
  max-width: 600px;
  margin: 0 20px;
}
.search-input {
  border-radius: 20px;
  background-color: #f7f9fa;
  border: 1px solid var(--udemy-dark);
}

/* Hero Banner */
.hero-wrapper {
  position: relative;
  background-color: #f8f9fb;
}
.hero-content-card {
  position: absolute;
  top: 50px;
  left: 50px;
  background: white;
  padding: 24px;
  max-width: 500px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.1);
  z-index: 2;
}
.hero-image-container img {
  width: 100%;
  height: 400px;
  object-fit: cover;
}

/* Course Card */
.course-card {
  transition: transform 0.2s;
  cursor: pointer;
}
.course-card:hover {
  transform: translateY(-5px);
}
.course-badge {
  background-color: #eceb98;
  color: #3d3c0a;
  font-weight: bold;
  padding: 4px 8px;
  font-size: 12px;
}

/* Stats Section */
.stats-section {
  background-color: var(--udemy-dark);
  color: white;
  padding: 40px 0;
}

/* Footer */
footer {
  background-color: var(--udemy-dark);
  color: white;
  font-size: 14px;
}
footer a {
  color: white;
  text-decoration: none;
}
footer a:hover {
  text-decoration: underline;
}

/* CSS để nút điều khiển hiển thị đẹp hơn */
.custom-carousel-control {
  width: 50px;
  height: 50px;
  top: 35%;
  opacity: 0.8;
}
.carousel-control-prev { left: -60px; }
.carousel-control-next { right: -60px; }

@media (max-width: 1200px) {
  .custom-carousel-control { display: none; }
}

.text-truncate-2 {
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.nav-link {
  font-weight: 500;
  color: #2d2f31 !important;
}
.nav-link:hover {
  color: #5624d0 !important;
}

.input-group-text, .form-control {
  border-color: #1c1d1f !important;
}

/* Auth Pages Styling */
.auth-page {
  background-color: #f7f9fc;
  min-height: 100vh;
  padding-top: 80px;
}

.card {
  padding: 1em;
  border: 1px solid #e1e3e6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--udemy-dark);
  margin-bottom: 8px;
}

.form-control,
.form-control:focus {
  border-color: #c5c8cb;
  font-size: 14px;
  padding: 10px 12px;
  height: 44px;
}

.form-control:focus {
  border-color: #1c1d1f;
  box-shadow: 0 0 0 0.2rem rgba(28, 29, 31, 0.15);
}

.input-group .btn {
  border: 1px solid #c5c8cb;
  color: #6a6f73;
  padding: 8px 12px;
}

.input-group .btn:hover,
.input-group .btn:focus {
  background-color: #f7f9fc;
  border-color: #1c1d1f;
  color: #1c1d1f;
}

.btn-dark {
  background-color: #1c1d1f;
  border-color: #1c1d1f;
  font-size: 14px;
  font-weight: 600;
  height: 44px;
  padding: 10px 24px;
}

.btn-dark:hover {
  background-color: #0f0f0f;
  border-color: #0f0f0f;
}

.btn-outline-dark {
  border: 2px solid #1c1d1f;
  color: #1c1d1f;
  font-weight: 600;
}

.btn-outline-dark:hover {
  background-color: #1c1d1f;
  color: white;
}

.form-check-input {
  width: 18px;
  height: 18px;
  border: 1px solid #c5c8cb;
  border-radius: 2px;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #1c1d1f;
  border-color: #1c1d1f;
}

.form-check-label {
  cursor: pointer;
  font-size: 14px;
  user-select: none;
}

.invalid-feedback {
  font-size: 13px;
  color: #d93026;
  margin-top: 5px;
}

.is-invalid,
.is-invalid:focus {
  border-color: #d93026 !important;
}

a {
  transition: color 0.2s ease;
  text-decoration: none;
}

.text-gray-600 {
  color: #6a6f73;
}

.auth-form-container {
  max-width: 500px;
  margin: 0 auto;
}

@media (max-width: 576px) {
  .card { border-radius: 0; }
  .card-body { padding: 32px 20px !important; }
}
</style>