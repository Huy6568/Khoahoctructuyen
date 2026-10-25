<script setup lang="ts">
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

type Props = {
  courseSlides: CourseCard[][]
  isLoading: boolean
  error: string | null
}

defineProps<Props>()
</script>

<template>
  <main class="homepage pb-5">
    <section class="hero-wrapper">
      <div class="container-fluid p-0 position-relative">
        <div class="hero-content-card d-none d-md-block">
          <h1 class="fw-bold h2">Nâng tầm kỹ năng, bứt phá sự nghiệp</h1>
          <p class="mt-3">
            Sở hữu các khóa học lập trình, kinh doanh và thiết kế từ những chuyên gia thực chiến.
          </p>
        </div>
        <div class="hero-image-container">
          <img
            src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=2071&auto=format&fit=crop"
            alt="Learning"
          />
        </div>
      </div>
    </section>

    <section class="container my-5">
      <h3 class="fw-bold mb-4">Khóa học mới nhất</h3>

      <div v-if="isLoading" class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Đang tải...</span>
        </div>
      </div>

      <div v-else-if="error" class="alert alert-danger text-center">
        {{ error }}
      </div>

      <div v-else id="courseCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner pb-4">
          <div
            v-for="(chunk, slideIndex) in courseSlides"
            :key="`slide-${slideIndex}`"
            class="carousel-item"
            :class="{ active: slideIndex === 0 }"
          >
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
              <div class="col" v-for="course in chunk" :key="course.id">
                <router-link :to="`/courses/${course.id}`" class="text-decoration-none text-dark d-block h-100">
                  <div class="card h-100 border-0 course-card">
                    <div class="position-relative">
                      <img :src="course.thumbnail" class="card-img-top border" :alt="course.title" style="height: 150px; object-fit: cover;">
                      <span v-if="course.priceRaw === 0" class="badge bg-success position-absolute top-0 end-0 m-2">
                        Miễn phí
                      </span>
                    </div>

                    <div class="card-body px-0">
                      <h6 class="card-title fw-bold mb-1 text-truncate-2" :title="course.title">
                        {{ course.title }}
                      </h6>
                      <p class="text-muted small mb-1">{{ course.instructorName }}</p>

                      <div class="text-warning small mb-1">
                        {{ course.ratingAverage.toFixed(1) }} <i class="bi bi-star-fill"></i>
                        <span class="text-muted">({{ course.ratingCount }})</span>
                      </div>

                      <div class="fw-bold fs-6">
                        {{ course.priceFormatted }}
                      </div>
                    </div>
                  </div>
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <template v-if="courseSlides.length > 1">
          <button class="carousel-control-prev custom-carousel-control" type="button" data-bs-target="#courseCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next custom-carousel-control" type="button" data-bs-target="#courseCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span>
          </button>
        </template>
      </div>
    </section>
  </main>
</template>

<style scoped>
.hero-wrapper { position: relative; background-color: #f8f9fb; }
.hero-content-card { position: absolute; top: 50px; left: 50px; background: #fff; padding: 24px; max-width: 500px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.1); z-index: 2; }
.hero-image-container img { width: 100%; height: 400px; object-fit: cover; }
.course-card { transition: transform 0.2s; cursor: pointer; }
.course-card:hover { transform: translateY(-5px); }
.custom-carousel-control { width: 50px; height: 50px; top: 35%; opacity: 0.8; }
.carousel-control-prev { left: -60px; }
.carousel-control-next { right: -60px; }
.text-truncate-2 { display: -webkit-box; line-clamp: 2; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
@media (max-width: 1200px) { .custom-carousel-control { display: none; } }
@media (max-width: 768px) { .hero-content-card { position: static; max-width: unset; margin: 0; box-shadow: none; } .hero-image-container img { height: 320px; } }
</style>
