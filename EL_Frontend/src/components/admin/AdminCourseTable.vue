<script setup lang="ts">
import PaginationControls from '../common/PaginationControls.vue'

type Course = {
  id: number
  title: string
  instructor: {
    fullname?: string
    name?: string
    full_name?: string
  } | null
  created_at: string
  status: 'pending' | 'published' | 'rejected' | string
}

type Props = {
  courses: Course[]
  isLoading: boolean
  meta: {
    current_page: number
    last_page: number
  }
  searchQuery: string
  statusFilter: string
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:searchQuery', value: string): void
  (event: 'update:statusFilter', value: string): void
  (event: 'applyFilters'): void
  (event: 'viewCourse', id: number): void
  (event: 'approve', id: number): void
  (event: 'reject', id: number): void
  (event: 'undo', id: number): void
  (event: 'changePage', page: number): void
}>()

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'pending': return 'status-pending'
    case 'published': return 'status-success'
    case 'rejected': return 'status-danger'
    default: return 'status-muted'
  }
}

const getInstructorName = (instructor: Course['instructor']) => {
  if (!instructor) return 'N/A'

  return instructor.fullname || instructor.name || instructor.full_name || 'N/A'
}
</script>

<template>
  <div class="admin-container">
    <div class="admin-toolbar">
      <form @submit.prevent="emit('applyFilters')" class="search-form">
        <input
          type="text"
          :value="searchQuery"
          @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
          placeholder="Tìm khóa học, giảng viên..."
          class="form-control"
        >
        <button type="submit" class="btn-search">
          <i class="bi bi-search"></i>
        </button>
      </form>

      <select
        class="form-control form-select"
        :value="statusFilter"
        @change="emit('update:statusFilter', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ duyệt</option>
        <option value="published">Chấp nhận</option>
        <option value="rejected">Từ chối</option>
      </select>
    </div>

    <div v-if="isLoading" class="text-center py-4 text-muted">Đang tải dữ liệu...</div>

    <div v-else class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th width="5%">ID</th>
            <th width="35%">Tên khóa học</th>
            <th width="20%">Giảng viên</th>
            <th width="15%">Ngày tạo</th>
            <th width="10%">Trạng thái</th>
            <th width="15%" class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="courses.length > 0">
            <tr v-for="course in courses" :key="course.id">
              <td>{{ course.id }}</td>
              <td><strong>{{ course.title }}</strong></td>
              <td>{{ getInstructorName(course.instructor) }}</td>
              <td>{{ formatDate(course.created_at) }}</td>
              <td>
                <span class="status-badge" :class="getStatusBadge(course.status)">
                  {{ course.status === 'published' ? 'Chấp nhận' : (course.status === 'rejected' ? 'Từ chối' : 'Chờ duyệt') }}
                </span>
              </td>
              <td class="action-buttons text-center">
                <button @click="emit('viewCourse', course.id)" class="btn-action" title="Xem">
                  <i class="bi bi-eye-fill"></i>
                </button>

                <template v-if="course.status === 'pending'">
                  <button @click="emit('approve', course.id)" class="btn-action btn-accept" title="Duyệt">
                    <i class="bi bi-check"></i>
                  </button>
                  <button @click="emit('reject', course.id)" class="btn-action btn-reject" title="Từ chối">
                    <i class="bi bi-x"></i>
                  </button>
                </template>
                <button v-else @click="emit('undo', course.id)" class="btn-action" title="Undo">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="6" class="text-center py-4 text-muted">Không tìm thấy khóa học nào.</td>
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
  flex: 0 0 auto;
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
  width: 260px;
  border-right: none;
}

.search-form .form-control:focus,
.form-select:focus {
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

.form-select {
  width: auto;
  background: #fff;
  cursor: pointer;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
}

.status-pending {
  background-color: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
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
  .admin-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-form,
  .form-select {
    max-width: none;
    width: 100%;
  }
}
</style>