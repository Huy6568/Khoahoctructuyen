<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'
import InstructorCourseList from '../../../components/instructor/InstructorCourseList.vue'

// 1. Định nghĩa Type cho khóa học của Giảng viên
interface Course {
    id: number
    title: string
    thumbnail_url: string
    status: 'published' | 'rejected' | 'pending' | string
}

const route = useRoute()
const router = useRouter()

// 2. States quản lý dữ liệu
const courses = ref<Course[]>([])
const isLoading = ref(true)
const meta = ref({ current_page: 1, last_page: 1 })

// Lấy giá trị mặc định từ URL (để khi F5 vẫn giữ được bộ lọc)
const searchQuery = ref(route.query.search as string || '')
const sortOrder = ref(route.query.sort as string || 'newest')

// 3. Hàm gọi API
const fetchCourses = async () => {
    isLoading.value = true
    try {
        // Gọi API với các tham số lấy từ URL
        const response = await api.get('/instructor/courses', {
            params: {
                search: route.query.search,
                sort: route.query.sort || 'newest',
                page: route.query.page || 1
            }
        })

        const payload = response.data
        const listData = payload?.data

        if (Array.isArray(listData)) {
            courses.value = listData
            meta.value = payload?.meta || { current_page: 1, last_page: 1 }
        } else if (listData && typeof listData === 'object' && Array.isArray(listData.data)) {
            courses.value = listData.data
            meta.value = {
                current_page: Number(listData.current_page || 1),
                last_page: Number(listData.last_page || 1),
            }
        } else {
            courses.value = []
            meta.value = { current_page: 1, last_page: 1 }
        }
    } catch (error) {
        console.error('Lỗi khi tải danh sách khóa học:', error)
    } finally {
        isLoading.value = false
    }
}

// 4. Xử lý sự kiện Tìm kiếm & Sắp xếp
const applyFilters = () => {
    // Thay vì submit form load lại trang, ta chỉ đổi URL
    router.push({
        path: '/instructor/courses',
        query: {
            search: searchQuery.value || undefined, // Nếu rỗng thì xóa khỏi URL
            sort: sortOrder.value,
            page: 1 // Tìm kiếm mới luôn bắt đầu từ trang 1
        }
    })
}

const changePage = (page: number) => {
    if (page < 1 || page > meta.value.last_page) return
    router.push({
        path: '/instructor/courses',
        query: { ...route.query, page: page.toString() }
    })
}

// 5. Lifecycle & Watchers
onMounted(() => {
    fetchCourses()
})

// Tự động gọi API lại khi URL thay đổi (Do bấm phân trang hoặc lọc)
watch(() => route.query, () => {
    fetchCourses()
}, { deep: true })

// 6. Hàm hỗ trợ giao diện
</script>

<template>
    <InstructorCourseList
      :courses="courses"
      :is-loading="isLoading"
      :meta="meta"
      :search-query="searchQuery"
      :sort-order="sortOrder"
      :has-success="Boolean(route.query.success)"
      @update:search-query="searchQuery = $event"
      @update:sort-order="sortOrder = $event"
      @apply-filters="applyFilters"
      @open-course="(id) => router.push(`/instructor/courses/builder/${id}`)"
      @change-page="changePage"
    />
</template>
