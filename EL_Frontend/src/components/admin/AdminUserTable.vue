<script setup lang="ts">
import PaginationControls from '../common/PaginationControls.vue'

type User = {
  id: number
  fullname: string
  email: string
  avatar?: string
  role: string
  status: 'active' | 'banned' | 'deleted' | string
}

type Props = {
  users: User[]
  isLoading: boolean
  meta: {
    current_page: number
    last_page: number
  }
  searchQuery: string
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:searchQuery', value: string): void
  (event: 'search'): void
  (event: 'ban', user: User): void
  (event: 'unban', user: User): void
  (event: 'changePage', page: number): void
}>()

const getStatusClass = (status: string) => {
  switch (status) {
    case 'active': return 'status-success'
    case 'banned': return 'status-danger'
    default: return 'status-muted'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'active': return 'Hoạt động'
    case 'banned': return 'Đã cấm'
    case 'deleted': return 'Đã xóa'
    default: return 'Không rõ'
  }
}
</script>

<template>
  <div class="admin-container">
    <div class="admin-toolbar">
      <form @submit.prevent="emit('search')" class="search-form">
        <input
          type="text"
          :value="searchQuery"
          @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
          placeholder="Tìm kiếm người dùng"
          class="form-control"
        >
        <button type="submit" class="btn-search">
          <i class="bi bi-search"></i>
        </button>
      </form>
    </div>

    <div v-if="isLoading" class="text-center py-4 text-muted">
      Đang tải dữ liệu...
    </div>

    <div v-else class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th width="45%">Người dùng</th>
            <th width="20%">Vai trò</th>
            <th width="20%">Trạng thái</th>
            <th width="15%" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="users.length > 0">
            <tr v-for="user in users" :key="user.id">
              <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${user.fullname}`"
                    :alt="user.fullname"
                    style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;"
                  >
                  <div>
                    <div style="font-weight: 600;">{{ user.fullname }}</div>
                    <div>{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                {{ user.role === 'admin' ? 'Quản trị viên' : 'Người dùng' }}
              </td>
              <td>
                <span class="status-badge" :class="getStatusClass(user.status)">
                  {{ getStatusText(user.status) }}
                </span>
              </td>
              <td>
                <div class="action-buttons text-center">
                  <button
                    v-if="user.status === 'active'"
                    @click="emit('ban', user)"
                    class="btn-action btn-reject"
                    title="Ban"
                  >
                    <i class="bi bi-ban"></i>
                  </button>

                  <button
                    v-if="user.status === 'banned'"
                    @click="emit('unban', user)"
                    class="btn-action btn-accept"
                    title="Unban"
                  >
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </button>
                </div>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="4" class="text-center py-4 text-muted">
              Không có người dùng nào phù hợp với tiêu chí tìm kiếm.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationControls
      class="mt-4"
      :current-page="meta.current_page"
      :last-page="meta.last_page"
      @change-page="(page) => emit('changePage', page)"
    />
  </div>
</template>

<style scoped>
.admin-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 12px;
}

.search-form {
  display: flex;
  align-items: center;
  flex: 1;
  max-width: 420px;
}

.form-control {
  height: 42px;
  padding: 0 12px;
  border: 1px solid #d1d7dc;
  font-size: 14px;
  border-radius: 0;
  outline: none;
}

.search-form .form-control {
  width: 100%;
  border-right: none;
}

.search-form .form-control:focus {
  border-color: #1c1d1f;
}

.btn-search {
  height: 42px;
  padding: 0 16px;
  border: 1px solid #1c1d1f;
  background: #1c1d1f;
  color: #fff;
  cursor: pointer;
}

.btn-search:hover {
  background: #333;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
}

.status-success {
  background-color: #f0fdf4;
  color: #15803d;
  border-color: #bbf7d0;
}

.status-danger {
  background-color: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

.status-muted {
  background-color: #f3f4f6;
  color: #374151;
  border-color: #d1d5db;
}

@media (max-width: 992px) {
  .search-form {
    max-width: none;
    width: 100%;
  }
}
</style>