<script setup lang="ts">
type Course = {
  id: number
  title: string
  thumbnail: string
  instructor: {
    name: string
  }
}

type Enrollment = {
  id: number
  purchase_price: number
  purchased_at: string
  course: Course
  progress_percentage?: number
  progress_percent?: number
  completed_lessons?: number
  total_lessons?: number
  progress?: {
    percentage?: number
    completed_lessons?: number
    total_lessons?: number
  }
}

type Pagination = {
  current_page: number
  last_page: number
  total: number
}

type Props = {
  enrollments: Enrollment[]
  pagination: Pagination | null
  isLoading: boolean
  errorMessage: string
  reviewMap: Record<number, { id: number; rating: number; comment: string } | null>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'retry'): void
  (event: 'goToLearn', courseId: number): void
  (event: 'changePage', page: number): void
  (event: 'openReview', courseId: number): void
}>()

const toSafePercent = (value: number) => {
  if (!Number.isFinite(value)) return 0
  return Math.max(0, Math.min(100, Math.round(value)))
}

const resolveProgressPercent = (item: Enrollment) => {
  const directPercent =
    item.progress_percentage ??
    item.progress_percent ??
    item.progress?.percentage

  if (typeof directPercent === 'number') {
    return toSafePercent(directPercent)
  }

  const completedLessons = item.completed_lessons ?? item.progress?.completed_lessons
  const totalLessons = item.total_lessons ?? item.progress?.total_lessons

  if (typeof completedLessons === 'number' && typeof totalLessons === 'number' && totalLessons > 0) {
    return toSafePercent((completedLessons / totalLessons) * 100)
  }

  return 0
}

const hasReviewed = (courseId: number) => {
  return Boolean(props.reviewMap?.[courseId])
}
</script>

<template>
  <div class="my-learning-page">
    <div class="learning-header">
      <div class="container">
        <h1 class="page-title">Học tập</h1>
      </div>
    </div>

    <div class="container learning-content">
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2 text-muted">Đang tải danh sách khóa học...</p>
      </div>

      <div v-else-if="errorMessage" class="alert alert-danger text-center">
        {{ errorMessage }}
        <button class="btn btn-sm btn-outline-danger ms-2" @click="emit('retry')">Thử lại</button>
      </div>

      <template v-else-if="enrollments?.length > 0">
        <div class="course-grid">
          <div v-for="item in enrollments" :key="item.id" class="course-card">
            <div class="card-media" @click="emit('goToLearn', item.course.id)">
              <img
                :src="item.course.thumbnail || 'https://picsum.photos/300/170'"
                :alt="item.course.title"
              >
              <div class="play-overlay">
                <i class="bi bi-play-circle-fill"></i>
              </div>
            </div>

            <div class="card-body">
              <h3 class="course-title">
                <a @click.prevent="emit('goToLearn', item.course.id)" href="#">
                  {{ item.course.title }}
                </a>
              </h3>
              <p class="instructor-name">
                {{ item.course.instructor?.name || 'Giảng viên' }}
              </p>

              <div class="course-progress" aria-label="Tiến độ học tập">
                <div class="progress-track">
                  <div class="progress-fill" :style="{ width: `${resolveProgressPercent(item)}%` }"></div>
                </div>
                <span class="progress-label">{{ resolveProgressPercent(item) }}%</span>
              </div>
            </div>

            <div class="card-footer">
              <button
                type="button"
                class="btn-rate-course"
                :class="{ 'is-reviewed': hasReviewed(item.course.id) }"
                @click="emit('openReview', item.course.id)"
              >
                <i class="bi" :class="hasReviewed(item.course.id) ? 'bi-star-fill' : 'bi-star'"></i>
                Đánh giá
              </button>
            </div>
          </div>
        </div>

        <div v-if="pagination && pagination.last_page > 1" class="pagination-wrapper mt-5">
          <button
            class="btn-page"
            :disabled="pagination.current_page === 1"
            @click="emit('changePage', pagination.current_page - 1)"
          > Trước </button>
          <span class="page-info">Trang {{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            class="btn-page"
            :disabled="pagination.current_page === pagination.last_page"
            @click="emit('changePage', pagination.current_page + 1)"
          > Sau </button>
        </div>
      </template>

      <div v-else class="empty-state">
        <h3 class="mb-3">Bạn chưa đăng ký khóa học nào.</h3>
        <p class="text-muted mb-4">Khám phá ngay các khóa học chất lượng để bắt đầu học.</p>
        <router-link to="/courses" class="btn-primary">Khám phá khóa học</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.my-learning-page { min-height: 80vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.learning-header { background-color: #1c1d1f; color: #fff; padding: 40px 0 30px; }
.page-title { font-size: 32px; font-weight: 700; margin: 0; }
.learning-content { padding: 40px 0; }
.course-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.course-card { border: 1px solid #d1d7dc; border-radius: 4px; overflow: hidden; display: flex; flex-direction: column; transition: 0.2s; }
.course-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.card-media { position: relative; cursor: pointer; aspect-ratio: 16/9; }
.card-media img { width: 100%; height: 100%; object-fit: cover; }
.play-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 40px; opacity: 0; transition: 0.2s; }
.card-media:hover .play-overlay { opacity: 1; }
.card-body { padding: 15px; flex-grow: 1; }
.course-title { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
.course-title a { color: #1c1d1f; text-decoration: none; }
.instructor-name { font-size: 12px; color: #6a6f73; }
.course-progress { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
.progress-track { flex: 1; height: 6px; border-radius: 999px; background: #e7eaee; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #22a06b 0%, #2fbf71 100%); transition: width 0.25s ease; }
.progress-label { font-size: 11px; font-weight: 700; color: #1c1d1f; min-width: 34px; text-align: right; }
.card-footer { padding: 10px 15px; border-top: 1px solid #d1d7dc; background: #f7f9fa; display: flex; justify-content: flex-end; }
.btn-rate-course { background: none; border: none; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-rate-course i { color: #e59819; }
.btn-rate-course.is-reviewed { color: #875a00; }
.empty-state { text-align: center; padding: 60px 0; }
.btn-primary { background: #a435f0; color: #fff; padding: 12px 24px; text-decoration: none; font-weight: 700; border-radius: 4px; display: inline-block; }
.pagination-wrapper { display: flex; justify-content: center; align-items: center; gap: 20px; }
.btn-page { padding: 8px 16px; border: 1px solid #d1d7dc; background: #fff; cursor: pointer; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
@media (max-width: 1100px) { .course-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .course-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .course-grid { grid-template-columns: 1fr; } }
</style>
