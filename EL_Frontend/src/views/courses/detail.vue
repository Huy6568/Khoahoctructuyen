<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CourseDetailPresentation from '../../components/courses/CourseDetailPresentation.vue'
import api from '../../api/axios'
import { useAuthStore } from '../../stores/auth' // Import store xác thực của bạn

// 1. Định nghĩa Type Interfaces dựa trên cấu trúc API
interface Lesson {
    id: number
    title: string
    content_type: string
}

interface Section {
    id: number
    title: string
    lessons: Lesson[]
}

interface Review {
    id: number
    rating: number
    comment: string
    created_at: string
    user: {
        fullname: string
        avatar_url?: string
    }
}

interface CourseDetail {
    id: number
    title: string
    description: string
    thumbnail: string
    video_preview_url: string | null
    price: { raw: number, formatted: string }
    rating: { average: number, count: number }
    instructor: {
        name: string
        id?: number | string
        user_id?: number | string
        instructor_id?: number | string
    }
    objectives: string[] | string | null
    updated_at: string
    sections: Section[]
    enrollments_count?: number
    video_count?: number
    article_count?: number
    stats?: {
        video_count: number
        article_count: number
        student_count: number
    }
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// 2. States
const course = ref<CourseDetail | null>(null)
const reviews = ref<Review[]>([])
const isEnrolled = ref(false)
const isLoading = ref(true)

const toNumberId = (value: unknown): number | null => {
    const numericValue = Number(value)
    return Number.isFinite(numericValue) ? numericValue : null
}

const isAdmin = computed(() => {
    const role = String(authStore.user?.role ?? '').toLowerCase()
    return role === 'admin' || role === 'super_admin' || role === 'superadmin'
})

const isCourseInstructor = computed(() => {
    const currentCourse = course.value
    if (!currentCourse || !authStore.user) {
        return false
    }

    const currentUserId = toNumberId(authStore.user.id)
    if (currentUserId === null) {
        return false
    }

    const instructor = currentCourse.instructor ?? {}
    const instructorId =
        toNumberId(instructor.id) ??
        toNumberId(instructor.user_id) ??
        toNumberId(instructor.instructor_id)

    if (instructorId === null) {
        return false
    }

    return instructorId === currentUserId
})

const hasLearningAccess = computed(() => {
    return isEnrolled.value || isAdmin.value || isCourseInstructor.value
})

const objectiveItems = computed(() => {
    const objectives = course.value?.objectives

    if (!objectives) return []

    if (Array.isArray(objectives)) {
        return objectives.map((objective) => objective.trim()).filter(Boolean)
    }

    return objectives
        .split(/\s*;;\s*/)
        .map((objective) => objective.trim())
        .filter(Boolean)
})

const videoCount = computed(() => {
    const currentCourse = course.value
    if (!currentCourse) return 0

    if (typeof currentCourse.stats?.video_count === 'number') {
        return currentCourse.stats.video_count
    }

    if (typeof currentCourse.video_count === 'number') {
        return currentCourse.video_count
    }

    const sections = currentCourse.sections || []
    return sections.reduce((total, section) => {
        const lessons = section.lessons || []
        const videos = lessons.filter((lesson) => lesson.content_type === 'video').length
        return total + videos
    }, 0)
})

const articleCount = computed(() => {
    const currentCourse = course.value
    if (!currentCourse) return 0

    if (typeof currentCourse.stats?.article_count === 'number') {
        return currentCourse.stats.article_count
    }

    if (typeof currentCourse.article_count === 'number') {
        return currentCourse.article_count
    }

    const sections = currentCourse.sections || []
    return sections.reduce((total, section) => {
        const lessons = section.lessons || []
        const articles = lessons.filter((lesson) => lesson.content_type === 'article').length
        return total + articles
    }, 0)
})

// 3. Lấy dữ liệu từ API
const fetchCourseDetail = async () => {
    try {
        isLoading.value = true
        // Gọi API: GET /api/courses/{id}
        const response = await api.get(`/courses/${route.params.id}`)
        
        course.value = response.data.data.course
        // Parse is_enrolled from multiple possible response structures
        const enrolledStatus = 
            response.data?.data?.is_enrolled ?? 
            response.data?.is_enrolled ?? 
            (response.data?.data?.course?.enrollment !== undefined)
        isEnrolled.value = Boolean(enrolledStatus)
        
        console.log('[Course Detail] enrollment status:', isEnrolled.value)
    } catch (error) {
        console.error("Lỗi khi tải chi tiết khóa học:", error)
        // Xử lý chuyển hướng nếu khóa học không tồn tại (404)
        router.push('/courses')
    } finally {
        isLoading.value = false
    }
}

const normalizeReviews = (raw: unknown): Review[] => {
    if (!Array.isArray(raw)) {
        return []
    }

    return raw.map((item) => {
        const source = item as Record<string, any>
        const user = (source.user ?? {}) as Record<string, any>

        return {
            id: Number(source.id ?? 0),
            rating: Number(source.rating ?? 0),
            comment: String(source.comment ?? ''),
            created_at: String(source.created_at ?? ''),
            user: {
                fullname: String(user.fullname ?? user.name ?? 'Học viên'),
                avatar_url: user.avatar_url ? String(user.avatar_url) : undefined,
            },
        }
    }).filter((review) => review.id > 0)
}

const fetchCourseReviews = async () => {
    try {
        const response = await api.get(`/courses/${route.params.id}/reviews`)
        const payload = response.data?.data ?? response.data ?? {}
        const reviewList = payload.reviews ?? payload.data ?? payload

        reviews.value = normalizeReviews(reviewList)
    } catch (error) {
        console.error('Lỗi khi tải đánh giá khóa học:', error)
        reviews.value = []
    }
}

onMounted(() => {
    fetchCourseDetail()
    fetchCourseReviews()
    
    // If returning from payment success, refetch after delay to ensure backend updated
    if (route.query.refresh === '1') {
        window.setTimeout(() => {
            fetchCourseDetail()
        }, 800)
    }
})

// Refetch data when route changes (e.g., user returns from payment success)
watch(
    () => route.params.id,
    () => {
        fetchCourseDetail()
        fetchCourseReviews()
    }
)

// Also check if we're returning from payment success
watch(
    () => route.query.refresh,
    (newVal) => {
        if (newVal === '1') {
            fetchCourseDetail()
            // Retry once more after delay to ensure enrollment updated
            window.setTimeout(() => {
                fetchCourseDetail()
            }, 500)
        }
    }
)

const handleEnrollClick = () => {
    if (authStore.isAuthenticated) {
        // Đã đăng nhập -> Sang trang thanh toán
        router.push(`/checkout/${course.value?.id}`)
    } else {
        // Chưa đăng nhập -> Sang trang login (có thể mang theo return_url)
        router.push(`/login?redirect=/courses/${course.value?.id}`)
    }
}

const goToLearning = () => {
    router.push(`/courses/${course.value?.id}/learn`)
}
</script>

<template>
    <div v-if="isLoading" class="container py-5 text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
    </div>

    <div v-else-if="course">
        <CourseDetailPresentation
          :course="course"
          :reviews="reviews"
          :objective-items="objectiveItems"
          :video-count="videoCount"
          :article-count="articleCount"
          :is-enrolled="isEnrolled"
                    :has-learning-access="hasLearningAccess"
          @enroll="handleEnrollClick"
          @go-learning="goToLearning"
        />
    </div>
</template>
