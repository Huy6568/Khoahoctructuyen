<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import api from '../../api/axios' // Import instance axios đã viết
import HomepageLanding from '../../components/home/HomepageLanding.vue'

type ApiCourse = {
	id: number
	title?: string
	instructor?: {
		name?: string
		fullname?: string
	}
	instructor_name?: string
	rating?: {
		average?: number
		count?: number
	}
	average_rating?: number
	reviews_count?: number
	price?: {
		raw?: number
		formatted?: string
	} | number
	thumbnail?: string
	thumbnail_url?: string
}

type CourseCard = {
	id: number
	title: string
	instructorName: string
	ratingAverage: number
	ratingCount: number
	priceRaw: number
	priceFormatted: string
	thumbnail: string
}

// Khởi tạo mảng rỗng và trạng thái tải dữ liệu
const courses = ref<CourseCard[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

const formatPrice = (value: number) => {
	if (value === 0) return 'Miễn phí'
	return `${new Intl.NumberFormat('vi-VN').format(value)}đ`
}

// Hàm gọi API lấy danh sách khóa học mới nhất
const fetchCourses = async () => {
	
	try {
		isLoading.value = true
		// Gọi endpoint GET /api/courses với filter status=published
		const response = await api.get('/courses', { params: { status: 'published' } })
		const payload = response.data?.data.courses ?? response.data.courses
		const rawCourses: ApiCourse[] = Array.isArray(payload) ? payload : []
		courses.value = rawCourses.map((course) => {
			const numericPrice = typeof course.price === 'number' ? course.price : (course.price?.raw ?? 0)
			const displayPrice = typeof course.price === 'number'
				? formatPrice(course.price)
				: (course.price?.formatted || formatPrice(numericPrice))

			return {
				id: course.id,
				title: course.title || 'Khóa học chưa có tiêu đề',
				instructorName: course.instructor?.name || course.instructor?.fullname || course.instructor_name || 'Không xác định',
				ratingAverage: Number(course.rating?.average ?? course.average_rating ?? 0),
				ratingCount: Number(course.rating?.count ?? course.reviews_count ?? 0),
				priceRaw: Number(numericPrice),
				priceFormatted: displayPrice,
				thumbnail: course.thumbnail || course.thumbnail_url || `https://picsum.photos/seed/course-${course.id}/300/170`
			}
		})
	} catch (err) {
		error.value = 'Không thể tải danh sách khóa học. Vui lòng thử lại sau.'
		console.error('API Error:', err)
	} finally {
		isLoading.value = false
	}
}

// Gọi API khi component được gắn vào DOM
onMounted(() => {
	fetchCourses()
})

const chunkSize = 4
const courseSlides = computed(() => {
	const result: CourseCard[][] = []
	if (!courses.value.length) return result
	for (let i = 0; i < courses.value.length; i += chunkSize) {
		result.push(courses.value.slice(i, i + chunkSize))
	}
	return result
})
</script>

<template>
  <NavHeader />
  <HomepageLanding
    :course-slides="courseSlides"
    :is-loading="isLoading"
    :error="error"
  />
</template>

<style scoped>
.hero-wrapper {
	position: relative;
	background-color: #f8f9fb;
}

.hero-content-card {
	position: absolute;
	top: 50px;
	left: 50px;
	background: #fff;
	padding: 24px;
	max-width: 500px;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.1);
	z-index: 2;
}

.hero-image-container img {
	width: 100%;
	height: 400px;
	object-fit: cover;
}

.course-card {
	transition: transform 0.2s;
	cursor: pointer;
}

.course-card:hover {
	transform: translateY(-5px);
}

.custom-carousel-control {
	width: 50px;
	height: 50px;
	top: 35%;
	opacity: 0.8;
}

.carousel-control-prev {
	left: -60px;
}

.carousel-control-next {
	right: -60px;
}

.text-truncate-2 {
	display: -webkit-box;
	line-clamp: 2;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

/* Ẩn nút mũi tên trên màn hình nhỏ để tránh đè lên thẻ khóa học */
@media (max-width: 1200px) {
	.custom-carousel-control {
		display: none;
	}
}

@media (max-width: 768px) {
	.hero-content-card {
		position: static;
		max-width: unset;
		margin: 0;
		box-shadow: none;
	}

	.hero-image-container img {
		height: 320px;
	}
}
</style>