<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'
import MyLearningView from '../../components/courses/MyLearningView.vue'

// --- TYPES (Cập nhật khớp với JSON thực tế) ---
interface Course {
  id: number
  title: string
  thumbnail: string
  instructor: {
    name: string
  }
}

interface Enrollment {
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

interface Pagination {
  current_page: number
  last_page: number
  total: number
}

interface ReviewPayload {
  id: number
  rating: number
  comment: string
}

// --- STATES ---
const router = useRouter()
const enrollments = ref<Enrollment[]>([]) // Khởi tạo mảng rỗng để tránh lỗi .length
const pagination = ref<Pagination | null>(null)
const isLoading = ref(true)
const errorMessage = ref('')
const reviewMap = ref<Record<number, ReviewPayload | null>>({})

const isReviewModalOpen = ref(false)
const activeCourseId = ref<number | null>(null)
const activeCourseTitle = ref('')
const selectedRating = ref(0)
const hoverRating = ref(0)
const reviewComment = ref('')
const reviewError = ref('')
const isSubmittingReview = ref(false)

// --- LOGIC XỬ LÝ ---

const fetchCourseProgress = async (courseIds: number[]) => {
  const uniqueCourseIds = [...new Set(courseIds.filter((id) => Number.isFinite(id) && id > 0))]

  if (!uniqueCourseIds.length) {
    return new Map<number, number>()
  }

  const progressEntries = await Promise.allSettled(
    uniqueCourseIds.map(async (courseId) => {
      const response = await api.get(`/courses/${courseId}/progress`)
      const rawProgress = Number(response.data?.data?.progress ?? 0)
      const progress = Math.max(0, Math.min(100, Number.isFinite(rawProgress) ? rawProgress : 0))
      return [courseId, progress] as const
    })
  )

  const progressMap = new Map<number, number>()

  progressEntries.forEach((entry) => {
    if (entry.status === 'fulfilled') {
      const [courseId, progress] = entry.value
      progressMap.set(courseId, progress)
    }
  })

  return progressMap
}

const normalizeMyReview = (raw: any): ReviewPayload | null => {
  const payload = raw?.data ?? raw ?? {}
  const reviewSource = payload?.review ?? payload?.data ?? payload

  if (!reviewSource || typeof reviewSource !== 'object') {
    return null
  }

  const rating = Number(reviewSource.rating ?? 0)
  if (!Number.isFinite(rating) || rating <= 0) {
    return null
  }

  return {
    id: Number(reviewSource.id ?? 0),
    rating: Math.max(1, Math.min(5, Math.round(rating))),
    comment: String(reviewSource.comment ?? ''),
  }
}

const fetchMyReview = async (courseId: number) => {
  try {
    const response = await api.get(`/courses/${courseId}/reviews/me`)
    const review = normalizeMyReview(response.data)

    reviewMap.value = {
      ...reviewMap.value,
      [courseId]: review,
    }

    return review
  } catch (error: any) {
    const status = Number(error?.response?.status ?? 0)

    if (status !== 404) {
      console.error(`Lỗi khi tải đánh giá của khóa học ${courseId}:`, error)
    }

    reviewMap.value = {
      ...reviewMap.value,
      [courseId]: null,
    }

    return null
  }
}

const fetchReviewStatuses = async (courseIds: number[]) => {
  const uniqueCourseIds = [...new Set(courseIds.filter((id) => Number.isFinite(id) && id > 0))]

  if (!uniqueCourseIds.length) {
    reviewMap.value = {}
    return
  }

  await Promise.allSettled(uniqueCourseIds.map((courseId) => fetchMyReview(courseId)))
}

const fetchMyLearning = async (page = 1) => {
  try {
    isLoading.value = true
    errorMessage.value = ''

    console.log(`--- Đang gọi API: /api/my-learning?page=${page} ---`)
    const res = await api.get('/my-learning', { params: { page } })

    // 1. Log toàn bộ dữ liệu nhận được từ API để kiểm tra
    console.log("Dữ liệu thô từ API (res.data):", res.data)

    // 2. Trích xuất dữ liệu dựa trên cấu trúc JSON bạn cung cấp
    const rawData = res.data.data
    const rawMeta = res.data.meta

    console.log("Danh sách enrollments (rawData):", rawData)
    console.log("Thông tin phân trang (rawMeta):", rawMeta)

    // 3. Gán dữ liệu (Dùng Optional Chaining và Default Value để an toàn)
    const enrollmentList = Array.isArray(rawData) ? rawData : []
    const courseIds = enrollmentList
      .map((item: Enrollment) => Number(item.course?.id ?? 0))
      .filter((id) => Number.isFinite(id) && id > 0)

    const progressMap = await fetchCourseProgress(
      courseIds
    )

    await fetchReviewStatuses(courseIds)

    enrollments.value = enrollmentList.map((item: Enrollment) => {
      const courseId = Number(item.course?.id ?? 0)
      const progressFromEndpoint = progressMap.get(courseId)

      if (typeof progressFromEndpoint === 'number') {
        return {
          ...item,
          progress_percentage: progressFromEndpoint,
        }
      }

      return item
    })
    
    if (rawMeta) {
      pagination.value = {
        current_page: rawMeta.current_page,
        last_page: rawMeta.last_page,
        total: rawMeta.total
      }
    }

    console.log("Dữ liệu sau khi gán vào State enrollments:", enrollments.value)

  } catch (error: any) {
    console.error("Lỗi khi tải danh sách học tập:", error)
    errorMessage.value = 'Không thể kết nối với máy chủ. Vui lòng thử lại.'
    enrollments.value = []
  } finally {
    isLoading.value = false
  }
}

const goToLearn = (courseId: number) => {
  router.push(`/courses/${courseId}/learning`)
}

const closeReviewModal = () => {
  if (isSubmittingReview.value) {
    return
  }

  isReviewModalOpen.value = false
  activeCourseId.value = null
  activeCourseTitle.value = ''
  selectedRating.value = 0
  hoverRating.value = 0
  reviewComment.value = ''
  reviewError.value = ''
}

const openReviewModal = async (courseId: number) => {
  const enrollment = enrollments.value.find((item) => item.course.id === courseId)
  if (!enrollment) {
    return
  }

  activeCourseId.value = courseId
  activeCourseTitle.value = enrollment.course.title
  reviewError.value = ''
  isReviewModalOpen.value = true

  const currentReview = await fetchMyReview(courseId)
  selectedRating.value = currentReview?.rating ?? 0
  reviewComment.value = currentReview?.comment ?? ''
}

const submitReview = async () => {
  const courseId = activeCourseId.value
  if (!courseId) {
    return
  }

  if (selectedRating.value < 1 || selectedRating.value > 5) {
    reviewError.value = 'Vui lòng chọn số sao từ 1 đến 5.'
    return
  }

  reviewError.value = ''
  isSubmittingReview.value = true

  try {
    const payload = {
      rating: selectedRating.value,
      comment: reviewComment.value.trim(),
    }

    if (reviewMap.value[courseId]) {
      await api.patch(`/courses/${courseId}/reviews`, payload)
    } else {
      await api.post(`/courses/${courseId}/reviews`, payload)
    }

    await fetchMyReview(courseId)
    closeReviewModal()
  } catch (error) {
    console.error('Lỗi khi lưu đánh giá:', error)
    reviewError.value = 'Không thể lưu đánh giá. Vui lòng thử lại.'
  } finally {
    isSubmittingReview.value = false
  }
}

const deleteReview = async () => {
  const courseId = activeCourseId.value
  if (!courseId || !reviewMap.value[courseId]) {
    return
  }

  const shouldDelete = window.confirm('Bạn có chắc chắn muốn xóa đánh giá này không?')
  if (!shouldDelete) {
    return
  }

  reviewError.value = ''
  isSubmittingReview.value = true

  try {
    await api.delete(`/courses/${courseId}/reviews`)
    reviewMap.value = {
      ...reviewMap.value,
      [courseId]: null,
    }
    closeReviewModal()
  } catch (error) {
    console.error('Lỗi khi xóa đánh giá:', error)
    reviewError.value = 'Không thể xóa đánh giá. Vui lòng thử lại.'
  } finally {
    isSubmittingReview.value = false
  }
}

onMounted(() => {
  fetchMyLearning()
})
</script>

<template>
  <div>
    <MyLearningView
      :enrollments="enrollments"
      :pagination="pagination"
      :is-loading="isLoading"
      :error-message="errorMessage"
      :review-map="reviewMap"
      @retry="fetchMyLearning(1)"
      @go-to-learn="goToLearn"
      @change-page="fetchMyLearning"
      @open-review="openReviewModal"
    />

    <div v-if="isReviewModalOpen" class="review-modal-backdrop" @click.self="closeReviewModal">
      <div class="review-modal-card">
        <h3 class="review-modal-title">Đánh giá khóa học</h3>
        <p class="review-modal-course">{{ activeCourseTitle }}</p>

        <div class="review-stars" role="radiogroup" aria-label="Chọn số sao">
          <button
            v-for="star in 5"
            :key="star"
            type="button"
            class="star-button"
            :class="{ active: star <= (hoverRating || selectedRating) }"
            :disabled="isSubmittingReview"
            @mouseenter="hoverRating = star"
            @mouseleave="hoverRating = 0"
            @click="selectedRating = star"
          >
            <i class="bi" :class="star <= (hoverRating || selectedRating) ? 'bi-star-fill' : 'bi-star'"></i>
          </button>
        </div>

        <label class="review-label" for="review-comment">Nội dung đánh giá</label>
        <textarea
          id="review-comment"
          v-model="reviewComment"
          class="review-textarea"
          rows="4"
          maxlength="1000"
          placeholder="Chia sẻ trải nghiệm học tập của bạn..."
          :disabled="isSubmittingReview"
        ></textarea>

        <p v-if="reviewError" class="review-error">{{ reviewError }}</p>

        <div class="review-actions">
          <button type="button" class="btn btn-outline-secondary" :disabled="isSubmittingReview" @click="closeReviewModal">
            Hủy
          </button>
          <button
            v-if="reviewMap[activeCourseId || 0]"
            type="button"
            class="btn btn-danger"
            :disabled="isSubmittingReview"
            @click="deleteReview"
          >
            Xóa đánh giá
          </button>
          <button type="button" class="btn btn-warning" :disabled="isSubmittingReview" @click="submitReview">
            {{ reviewMap[activeCourseId || 0] ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.review-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.52);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  padding: 16px;
}

.review-modal-card {
  width: 100%;
  max-width: 540px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 18px 45px rgba(0, 0, 0, 0.2);
  padding: 22px;
}

.review-modal-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.review-modal-course {
  margin: 6px 0 16px;
  color: #4f5357;
  font-size: 14px;
}

.review-stars {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.star-button {
  border: 0;
  background: transparent;
  font-size: 28px;
  color: #c6cacf;
  cursor: pointer;
  padding: 0;
}

.star-button.active {
  color: #e59819;
}

.review-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 600;
}

.review-textarea {
  width: 100%;
  border: 1px solid #d1d7dc;
  border-radius: 10px;
  padding: 10px 12px;
  resize: vertical;
  min-height: 100px;
}

.review-textarea:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

.review-error {
  margin: 10px 0 0;
  color: #c62828;
  font-size: 13px;
}

.review-actions {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

@media (max-width: 560px) {
  .review-modal-card {
    padding: 16px;
  }

  .star-button {
    font-size: 24px;
  }
}
</style>
