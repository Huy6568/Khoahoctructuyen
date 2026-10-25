<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CourseFilters from '../../components/courses/CourseFilters.vue'
import CourseCatalog from '../../components/courses/CourseCatalog.vue'
import api from '../../api/axios' // Import axios đã cấu hình của bạn

// 1. Cập nhật Type khớp 100% với cấu trúc CourseResource và CategoryResource
interface Category {
  id: number
  name: string
  // Tùy thuộc vào backend của bạn, nếu có status thì giữ lại
}

interface Course {
  id: number
  title: string
  description: string
  thumbnail: string // Đã đổi theo Resource
  rating: {
    average: number
    count: number
  }
  price: {
    raw: number
    formatted: string
  }
  instructor: {
    id: number
    name: string
  }
  category_id: number
}

// Khởi tạo Router để thao tác với URL
const route = useRoute()
const router = useRouter()

// 2. State quản lý dữ liệu từ API
const courses = ref<Course[]>([])
const categories = ref<Category[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

// State quản lý Phân trang (Lấy từ meta của API)
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

// State quản lý Bộ lọc UI
const selectedCategories = ref<number[]>([])
const searchKeyword = ref('')

// 3. Hàm gọi API lấy dữ liệu
const fetchCourses = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // Truyền toàn bộ route.query (gồm search, page, categories) lên Laravel API
    // Thêm status=published để chỉ hiển thị khóa học đã xuất bản
    // Ví dụ: GET /api/courses?page=1&search=vue&categories[]=1&status=published
    const response = await api.get('/courses', { params: { ...route.query, status: 'published' } })
    
    // Gán dữ liệu từ API vào State
    courses.value = response.data.data.courses
    categories.value = response.data.data.categories
    meta.value = response.data.meta
    
  } catch (err) {
    console.error('Lỗi khi lấy danh sách khóa học:', err)
    error.value = 'Không thể tải dữ liệu. Vui lòng kiểm tra kết nối.'
  } finally {
    isLoading.value = false
  }
}

// 4. Đồng bộ URL xuống State (Khi load trang hoặc back/forward trình duyệt)
const syncFiltersFromUrl = () => {
  if (route.query.categories) {
    const cats = route.query.categories as string | string[]
    selectedCategories.value = Array.isArray(cats) ? cats.map(Number) : [Number(cats)]
  } else {
    selectedCategories.value = []
  }

  const search = route.query.search
  searchKeyword.value = typeof search === 'string' ? search : ''
}

// 5. Lifecycle Hooks
onMounted(() => {
  syncFiltersFromUrl()
  fetchCourses()
})

// Theo dõi sự thay đổi của URL. Nếu URL đổi (bấm trang 2, lọc), tự động gọi lại API
watch(
  () => route.query,
  () => {
    syncFiltersFromUrl()
    fetchCourses()
  },
  { deep: true }
)

// 6. Hàm xử lý sự kiện UI
const applyFilters = () => {
  // Thay đổi URL -> Watcher sẽ tự động kích hoạt fetchCourses()
  router.push({
    path: '/courses',
    query: {
      ...route.query, // Giữ lại từ khóa search nếu có
      categories: selectedCategories.value,
      page: 1 // Luôn reset về trang 1 khi lọc mới
    }
  })
}

const clearFilters = () => {
  selectedCategories.value = []
  router.push({
    path: '/courses',
    query: { search: route.query.search } // Giữ lại mỗi search
  })
}

const changePage = (page: number) => {
  if (page < 1 || page > meta.value.last_page) return
  
  router.push({
    path: '/courses',
    query: { ...route.query, page: page.toString() }
  })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <div class="container py-5">
    <div class="row">
      <CourseFilters
        :categories="categories"
        v-model:selected-categories="selectedCategories"
        @apply="applyFilters"
        @clear="clearFilters"
      />

      <CourseCatalog
        :courses="courses"
        :meta="meta"
        :is-loading="isLoading"
        :error="error"
        :search-keyword="searchKeyword"
        @change-page="changePage"
      />
    </div>
  </div>
</template>
