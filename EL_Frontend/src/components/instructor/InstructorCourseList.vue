<script setup lang="ts">
import PaginationControls from '../common/PaginationControls.vue'

type Course = {
  id: number
  title: string
  thumbnail_url: string
  status: 'published' | 'rejected' | 'pending' | string
}

type Meta = {
  current_page: number
  last_page: number
}

type Props = {
  courses: Course[]
  isLoading: boolean
  meta: Meta
  searchQuery: string
  sortOrder: string
  hasSuccess: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:searchQuery', value: string): void
  (event: 'update:sortOrder', value: string): void
  (event: 'applyFilters'): void
  (event: 'openCourse', id: number): void
  (event: 'changePage', page: number): void
}>()

const getStatusText = (status: string) => {
  switch (status) {
    case 'published': return 'Đã xuất bản'
    case 'rejected': return 'Đã từ chối'
    default: return 'Chờ duyệt'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'published': return '#28a745'
    case 'rejected': return '#dc3545'
    default: return '#ffc107'
  }
}
</script>

<template>
  <div class="content-area">
    <div v-if="hasSuccess" class="alert alert-success mb-3">
      Thao tác thành công!
    </div>

    <div class="toolbar">
      <div class="toolbar-left">
        <form @submit.prevent="emit('applyFilters')" class="search-group">
          <input
            type="text"
            :value="searchQuery"
            @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
            placeholder="Tìm kiếm khóa học của bạn"
          >
          <button type="submit" class="btn-search">
            <i class="bi bi-search"></i>
          </button>
        </form>

        <select class="sort-dropdown" :value="sortOrder" @change="emit('update:sortOrder', ($event.target as HTMLSelectElement).value)">
          <option value="newest">Mới nhất</option>
          <option value="oldest">Cũ nhất</option>
        </select>
      </div>

      <router-link to="/instructor/courses/builder" class="btn-create-course">
        Tạo khóa học
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      Đang tải dữ liệu...
    </div>

    <div v-else class="course-list">
      <template v-if="courses.length > 0">
        <div
          v-for="course in courses"
          :key="course.id"
          class="course-card"
          @click="emit('openCourse', course.id)"
        >
          <img :src="course.thumbnail_url || 'https://picsum.photos/250/140'" :alt="course.title" class="course-thumbnail">
          <div class="course-info">
            <h3>{{ course.title }}</h3>
            <span class="course-status" :style="{ color: getStatusColor(course.status) }">
              {{ getStatusText(course.status) }}
            </span>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <p>Bạn chưa có khóa học nào phù hợp. Hãy bấm "Tạo khóa học" để bắt đầu!</p>
      </div>
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
:root {
  --primary-color: #a435f0;
  --border-color: #d1d7dc;
  --text-color: #1c1d1f;
}

.content-area {
  padding: 32px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-group {
  display: flex;
  align-items: center;
}

.search-group input {
  height: 42px;
  width: 260px;
  padding: 0 12px;
  border: 1px solid var(--border-color);
  border-right: none;
  outline: none;
}

.search-group input:focus {
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

.sort-dropdown {
  height: 42px;
  padding: 0 12px;
  border: 1px solid var(--border-color);
  background: #fff;
  outline: none;
  cursor: pointer;
}

.btn-create-course {
  background: #8710d8;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 42px;
  padding: 0 18px;
  background-color: #8710d8;
  color: #fff;
  text-decoration: none;
  font-weight: 700;
}

.btn-create-course:hover {
  opacity: 0.8;
}

.course-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.course-card {
  display: flex;
  align-items: stretch;
  min-height: 138px;
  border: 1px solid var(--border-color);
  background: #fff;
  cursor: pointer;
  transition: box-shadow 0.2s ease;
}

.course-card:hover {
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
}

.course-thumbnail {
  width: 220px;
  object-fit: cover;
  border-right: 1px solid var(--border-color);
}

.course-info {
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.course-info h3 {
  margin: 0;
  font-size: 18px;
  color: var(--text-color);
}

.course-status {
  font-size: 13px;
  font-weight: 700;
}

.empty-state {
  padding: 36px;
  border: 1px dashed var(--border-color);
  text-align: center;
  color: #6a6f73;
}

@media (max-width: 992px) {
  .content-area {
    padding: 20px;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-left {
    flex-direction: column;
    align-items: stretch;
  }

  .search-group input,
  .sort-dropdown,
  .btn-create-course {
    width: 100%;
  }

  .course-card {
    flex-direction: column;
  }

  .course-thumbnail {
    width: 100%;
    height: 170px;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
}
</style>