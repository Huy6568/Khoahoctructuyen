<script setup lang="ts">
import { computed } from 'vue'
import NotificationPopup from '../common/NotificationPopup.vue'

type User = {
  fullname?: string
  name?: string
  role?: string
  avatar?: string
}

type Props = {
  isAuthenticated: boolean
  user: User
  searchQuery: string
  applicationLogo: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:searchQuery', value: string): void
  (event: 'search'): void
  (event: 'logout'): void
}>()

const userAvatar = computed(() => {
  if (props.user.avatar) return props.user.avatar
  const name = props.user.fullname || props.user.name || 'User'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}`
})

const normalizedRole = computed(() => String(props.user?.role ?? '').trim().toLowerCase())

const isAdminRole = computed(() => {
  return normalizedRole.value === 'admin' || normalizedRole.value === 'super_admin' || normalizedRole.value === 'superadmin'
})

const isInstructorRole = computed(() => {
  return normalizedRole.value === 'user' || normalizedRole.value === 'instructor' || normalizedRole.value === 'teacher'
})
</script>

<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2">
    <div class="container-fluid px-4">
      <router-link class="navbar-brand me-4" to="/">
        <div style="width: 40px;">
          <img :src="applicationLogo" alt="Logo" class="img-fluid" />
        </div>
      </router-link>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#udemyNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="udemyNavbar">
        <ul class="navbar-nav mb-2 mb-lg-0">
          <li class="nav-item">
            <router-link class="nav-link text-dark" to="/courses">Tìm khóa học</router-link>
          </li>
        </ul>

        <form class="flex-grow-1 mx-4 d-none d-lg-block" @submit.prevent="emit('search')">
          <div class="input-group">
            <span class="input-group-text bg-light border-dark rounded-start-pill border-end-0 ps-3">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input
              type="text"
              class="form-control bg-light border-dark rounded-end-pill border-start-0 py-2"
              placeholder="Tìm kiếm khóa học"
              :value="searchQuery"
              @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
            >
          </div>
        </form>

        <ul class="navbar-nav align-items-center">
          <li class="nav-item d-none d-xl-block">
            <template v-if="isAuthenticated">
              <router-link v-if="isAdminRole" class="nav-link text-dark px-3" to="/admin/users">Quản trị viên</router-link>
              <router-link v-else-if="isInstructorRole" class="nav-link text-dark px-3" to="/instructor/courses">Giảng viên</router-link>
            </template>
            <template v-if="!isAuthenticated">
              <router-link class="nav-link text-dark px-3" to="/login">Giảng viên</router-link>
            </template>
          </li>

          <li class="nav-item d-none d-xl-block">
            <router-link class="nav-link text-dark px-3" to="/my-learning">Học tập</router-link>
          </li>

          <!-- <li class="nav-item px-2">
            <router-link class="nav-link fs-5 text-dark" to="/cart"></router-link>
          </li> -->

          <template v-if="isAuthenticated">
            <li class="nav-item px-2 d-flex align-items-center">
              <NotificationPopup />
            </li>

            <li class="nav-item dropdown ms-2">
              <a class="nav-link p-0" href="#" id="userMenu" data-bs-toggle="dropdown">
                <img :src="userAvatar" class="rounded-circle border" width="35" height="35" alt="Avatar">
              </a>

              <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="min-width: 200px;">
                <li><div class="dropdown-header fw-bold text-dark fs-6">{{ user?.fullname }}</div></li>
                <li><hr class="dropdown-divider"></li>

                <li><router-link class="dropdown-item py-2" v-if="isInstructorRole" to="/instructor/profile">Thông tin cá nhân</router-link></li>
                <li><router-link class="dropdown-item py-2" v-if="isAdminRole" to="/admin/profile">Thông tin cá nhân</router-link></li>

                <!-- <li><hr class="dropdown-divider"></li> -->
                <li><router-link class="dropdown-item py-2" to="/my-learning">Khóa học</router-link></li>

                <li><hr class="dropdown-divider"></li>

                <li>
                  <button @click="emit('logout')" class="dropdown-item text-danger py-2 w-100 text-start">
                    Đăng xuất
                  </button>
                </li>
              </ul>
            </li>
          </template>

          <template v-else>
            <li class="nav-item ms-3 d-flex">
              <router-link to="/login" class="btn btn-outline-dark rounded-0 fw-bold px-4 me-2">Đăng nhập</router-link>
              <router-link to="/register" class="btn btn-dark rounded-0 fw-bold px-4">Đăng ký</router-link>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </nav>
</template>