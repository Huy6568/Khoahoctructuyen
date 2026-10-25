<script setup lang="ts">
type Course = {
  id: number
  title: string
  description: string
  thumbnail: string
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

type Meta = {
  current_page: number
  last_page: number
  total: number
}

type Props = {
  courses: Course[]
  meta: Meta
  isLoading: boolean
  error: string | null
  searchKeyword?: string
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'changePage', page: number): void
}>()

const onChangePage = (page: number) => {
  emit('changePage', page)
}
</script>

<template>
  <main class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Tất cả khóa học</h3>
      <span v-if="searchKeyword" class="text-muted ms-3">
        Kết quả cho: <strong>"{{ searchKeyword }}"</strong>
      </span>
      <span class="text-muted ms-auto">Tìm thấy {{ meta.total }} kết quả</span>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Đang tải...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger text-center">
      {{ error }}
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
      <template v-if="courses.length > 0">
        <div class="col" v-for="course in courses" :key="course.id">
          <router-link :to="`/courses/${course.id}`" class="text-decoration-none text-dark d-block h-100">
            <div class="card h-100 border-0 course-card">
              <img :src="course.thumbnail" class="card-img-top border" :alt="course.title" style="height: 150px; object-fit: cover;">

              <div class="card-body px-0 pt-2 pb-0 d-flex flex-column">
                <h6 class="card-title fw-bold mb-2" :title="course.title">
                  {{ course.title }}
                </h6>

                <p class="text-muted small mb-2">
                  <strong>Giảng viên:</strong> {{ course.instructor?.name }}
                </p>

                <div class="mb-2 flex-grow-1">
                  <small class="text-muted course-desc-clamp"><strong>Mô tả:</strong> {{ course.description || 'Chưa có mô tả' }}</small>
                </div>

                <div class="mb-2">
                  <small class="fw-semibold" style="color: #c4710d;">
                    {{ course.rating?.average }} <i class="bi bi-star-fill"></i>
                    ({{ new Intl.NumberFormat('vi-VN').format(course.rating?.count || 0) }})
                  </small>
                </div>

                <hr class="my-2">

                <div class="course-price fw-bold fs-6 text-primary">
                  {{ course.price.raw === 0 ? 'Miễn phí' : course.price.formatted }}
                </div>
              </div>
            </div>
          </router-link>
        </div>
      </template>

      <div v-else class="col-12 text-center py-5">
        <h5 class="text-muted">Chưa có khóa học nào phù hợp với bộ lọc.</h5>
      </div>
    </div>

    <nav v-if="!isLoading && meta.last_page > 1" class="mt-4" aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
          <button class="page-link" @click="onChangePage(meta.current_page - 1)">Trang trước</button>
        </li>

        <li v-for="page in meta.last_page" :key="page" class="page-item" :class="{ active: page === meta.current_page }">
          <button class="page-link" @click="onChangePage(page)">{{ page }}</button>
        </li>

        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
          <button class="page-link" @click="onChangePage(meta.current_page + 1)">Trang sau</button>
        </li>
      </ul>
    </nav>
  </main>
</template>

<style scoped>
.course-card .card-title,
.course-card .course-desc-clamp {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
}

.course-card {
  transition: transform 0.2s;
}

.course-card:hover {
  transform: translateY(-4px);
}

.pagination .page-link {
  color: #2d2f31;
}

.pagination .active .page-link {
  background-color: #2d2f31;
  border-color: #2d2f31;
  color: white;
}
</style>
