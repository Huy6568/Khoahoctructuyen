<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/axios'
import CourseLearningView from '../../components/courses/CourseLearningView.vue'
import { useAuthStore } from '../../stores/auth'

// --- TYPES ---
interface Lesson {
  id: number
  title: string
  content_type: 'video' | 'article'
  video_url?: string
  article_content?: string
  is_completed?: boolean
  completed?: boolean
}

interface Section {
  id: number
  title: string
  lessons: Lesson[]
}

interface Course {
  id: number
  title: string
  sections: Section[]
}

// --- STATES ---
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const course = ref<Course | null>(null)
const currentLesson = ref<Lesson | null>(null)
const isLoading = ref(true)

const isAdmin = computed(() => {
  const role = String(authStore.user?.role ?? '').toLowerCase()
  return role === 'admin' || role === 'super_admin' || role === 'superadmin'
})

const allowProgressUpdate = computed(() => !isAdmin.value)

// // Review Modal States
// const isReviewModalOpen = ref(false)
// const selectedRating = ref(0)
// const hoverRating = ref(0)
// const reviewComment = ref('')

// --- LOGIC ---

const resolveCourseId = () => {
  const courseIdFromParam = route.params.courseId
  if (typeof courseIdFromParam === 'string' && courseIdFromParam.trim()) {
    return courseIdFromParam
  }

  const idFromParam = route.params.id
  if (typeof idFromParam === 'string' && idFromParam.trim()) {
    return idFromParam
  }

  return ''
}

const normalizeLearningCourse = (rawCourse: any): Course | null => {
  if (!rawCourse || typeof rawCourse !== 'object') {
    return null
  }

  const sectionSources = Array.isArray(rawCourse.sections) ? rawCourse.sections : []

  return {
    id: Number(rawCourse.id ?? 0),
    title: String(rawCourse.title ?? ''),
    sections: sectionSources.map((section: any) => {
      const lessonSources = Array.isArray(section.lessons)
        ? section.lessons
        : Array.isArray(section.lectures)
          ? section.lectures
          : []

      return {
        id: Number(section.id ?? 0),
        title: String(section.title ?? ''),
        lessons: lessonSources.map((lesson: any) => ({
          id: Number(lesson.id ?? 0),
          title: String(lesson.title ?? ''),
          content_type: (lesson.content_type ?? lesson.type ?? 'video') as 'video' | 'article',
          video_url: lesson.video_url ?? lesson?.content?.url ?? undefined,
          article_content: lesson.article_content ?? lesson?.content?.text ?? undefined,
          is_completed: Boolean(lesson.is_completed ?? lesson.completed ?? lesson.isCompleted ?? false),
          completed: Boolean(lesson.completed ?? lesson.is_completed ?? lesson.isCompleted ?? false),
        })),
      }
    }),
  }
}

const fetchData = async () => {
  try {
    isLoading.value = true
    const courseId = resolveCourseId()
    const lessonId = route.params.lessonId

    if (!courseId) {
      router.push('/courses')
      return
    }

    const learningEndpoints = isAdmin.value
      ? [
          `/courses/${courseId}/learning`,
          `/admin/courses/${courseId}/learning`,
          `/admin/courses/${courseId}`,
          `/courses/${courseId}`,
        ]
      : [`/courses/${courseId}/learning`]

    let normalizedCourse: Course | null = null
    let lastError: unknown = null

    for (const endpoint of learningEndpoints) {
      try {
        const res = await api.get(endpoint)
        const payload = res.data?.data ?? res.data ?? {}
        const rawCourse = payload.course ?? payload
        normalizedCourse = normalizeLearningCourse(rawCourse)

        if (normalizedCourse?.sections?.length) {
          break
        }
      } catch (error) {
        lastError = error
      }
    }

    if (!normalizedCourse?.sections?.length) {
      throw lastError ?? new Error('Không thể tải nội dung bài học')
    }

    course.value = normalizedCourse

    // 2. Xác định bài học hiện tại
    if (lessonId) {
      const allLessons = course.value?.sections.flatMap(s => s.lessons) || []
      currentLesson.value = allLessons.find(l => l.id === Number(lessonId)) || null
    } else if (course.value?.sections[0]?.lessons[0]) {
      // Nếu không có lessonId trên URL, mặc định lấy bài đầu tiên
      currentLesson.value = course.value.sections[0].lessons[0]
    }
  } catch (error) {
    console.error("Lỗi khi tải bài học:", error)
    router.push('/courses')
  } finally {
    isLoading.value = false
  }
}

// Theo dõi sự thay đổi của lessonId trên URL để cập nhật nội dung
watch(() => route.params.lessonId, () => {
  fetchData()
})

// Star Rating Logic
// const setRating = (rating: number) => {
//   selectedRating.value = rating
// }

// const submitReview = async () => {
//   if (selectedRating.value === 0) {
//     alert('Vui lòng chọn số sao trước khi đánh giá.')
//     return
//   }

//   try {
//     await api.post(`/courses/${course.value?.id}/reviews`, {
//       rating: selectedRating.value,
//       comment: reviewComment.value
//     })
//     alert('Cảm ơn bạn đã đánh giá!')
//     isReviewModalOpen.value = false
//   } catch (error) {
//     alert('Có lỗi xảy ra khi gửi đánh giá.')
//   }
// }

onMounted(fetchData)
</script>

<template>
  <div v-if="isLoading" class="loading-state">Đang tải nội dung bài học...</div>
  <CourseLearningView
    v-else-if="course && currentLesson"
    :course="course"
    :current-lesson="currentLesson"
    :allow-progress-update="allowProgressUpdate"
  />
</template>
