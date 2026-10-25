<script setup lang="ts">
import { computed } from 'vue'

type Lesson = {
  id: number
  title: string
  content_type: string
}

type Section = {
  id: number
  title: string
  lessons: Lesson[]
}

type Review = {
  id: number
  rating: number
  comment: string
  created_at: string
  user: {
    fullname: string
    avatar_url?: string
  }
}

type CourseDetail = {
  id: number
  title: string
  description: string
  thumbnail: string
  video_preview_url: string | null
  price: { raw: number; formatted: string }
  rating: { average: number; count: number }
  instructor: { name?: string; fullname?: string }
  objectives: string[] | string | null
  updated_at: string
  sections: Section[]
  enrollments_count?: number
}

type Props = {
  course: CourseDetail
  reviews: Review[]
  objectiveItems: string[]
  videoCount: number
  articleCount: number
  isEnrolled: boolean
  hasLearningAccess: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'enroll'): void
  (event: 'go-learning'): void
}>()

const formatDate = (dateString: string) => {
  if (!dateString) return 'không xác định'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const onEnroll = () => {
  emit('enroll')
}

const onGoLearning = () => {
  emit('go-learning')
}

const accessMessage = computed(() => {
  if (props.isEnrolled) {
    return 'Bạn đã sở hữu khóa học này.'
  }

  return 'Bạn có quyền truy cập khóa học này.'
})

const instructorName = computed(() => {
  return props.course?.instructor?.name || props.course?.instructor?.fullname || 'Giảng viên'
})

const averageRating = computed(() => {
  const serverAvg = props.course?.rating?.average
  if (serverAvg != null && !isNaN(Number(serverAvg))) return Number(serverAvg)
  if (props.reviews && props.reviews.length > 0) {
    const sum = props.reviews.reduce((s, r) => s + (r.rating || 0), 0)
    return sum / props.reviews.length
  }
  return 0
})

const formattedAverage = computed(() => {
  return averageRating.value ? averageRating.value.toFixed(1) : '0.0'
})

// const averageStars = computed(() => Math.round(averageRating.value || 0))
</script>

<template>
  <div class="course-hero">
    <div class="container hero-content">
      <div class="hero-text-area">
        <h1 class="course-title">{{ course.title }}</h1>

        <div class="course-meta">
          <span class="rating">
            <span class="rating-number">
              <span class="rating-value">{{ formattedAverage }} <i class="bi bi-star-fill"></i></span>
            </span>
            <a href="#reviews" class="review-count">{{ reviews.length }} đánh giá</a>
          </span>
          <span class="student-count">{{ course.enrollments_count || 0 }} học viên</span>
        </div>

        <div class="course-creator">
          Được tạo bởi <a href="#">{{ instructorName }}</a>
        </div>

        <div class="course-updated">
          <i class="bi bi-exclamation-circle-fill"></i> Cập nhật lần cuối {{ formatDate(course.updated_at) }}
        </div>
      </div>
    </div>
  </div>

  <div class="container course-main-layout">
    <div class="course-left-col">
      <div class="section-box objectives-box">
        <h2>Bạn sẽ học được gì</h2>
        <ul class="objectives-list">
          <template v-if="objectiveItems.length > 0">
            <li v-for="(objective, index) in objectiveItems" :key="index">
              <i class="bi bi-check-lg"></i> {{ objective }}
            </li>
          </template>
          <li v-else>
            <i class="bi bi-check-lg"></i> Chưa có mục tiêu học tập.
          </li>
        </ul>
      </div>

      <div class="section-box curriculum-box">
        <h2>Nội dung khóa học</h2>

        <div class="accordion-container">
          <template v-if="course.sections && course.sections.length > 0">
            <details v-for="(section, index) in course.sections" :key="section.id" class="accordion-item" :open="index === 0">
              <summary class="accordion-header">
                <span class="accordion-title">
                  <i class="bi bi-chevron-down toggle-icon"></i>
                  Chương {{ index + 1 }}: {{ section.title }}
                </span>
                <span class="accordion-stats">{{ section.lessons?.length || 0 }} bài giảng</span>
              </summary>
              <div class="accordion-body">
                <ul class="lesson-list">
                  <template v-if="section.lessons && section.lessons.length > 0">
                    <li v-for="lesson in section.lessons" :key="lesson.id">
                      <span>
                        <i class="lesson-icon" :class="lesson.content_type === 'video' ? 'bi-play-btn-fill' : 'bi-file-earmark-text'"></i>
                        {{ lesson.title }}
                      </span>
                    </li>
                  </template>
                  <li v-else>Chưa có bài giảng.</li>
                </ul>
              </div>
            </details>
          </template>
          <p v-else>Chưa có nội dung chương trình học.</p>
        </div>
      </div>

      <div class="section-box description-box">
        <h2>Mô tả</h2>
        <div class="description-content">
          <p>{{ course.description || 'Khóa học không có mô tả' }}</p>
        </div>
      </div>

      <div id="reviews" class="section-box reviews-box">
        <h2>Đánh giá từ học viên</h2>
        <div class="review-list">
          <template v-if="reviews.length > 0">
            <div v-for="review in reviews" :key="review.id" class="review-item">
              <img :src="review.user.avatar_url || `https://ui-avatars.com/api/?name=${review.user.fullname}`" alt="Avatar" class="review-avatar">
              <div class="review-content">
                <h4 class="reviewer-name">{{ review.user.fullname }}</h4>
                <div class="review-stars">
                  <i v-for="n in 5" :key="n" class="bi" :class="n <= review.rating ? 'bi-star-fill' : 'bi-star'" style="color: #c4710d;"></i>
                </div>
                <div class="review-meta">
                  <span class="review-date">{{ formatDate(review.created_at) }}</span>
                </div>
                <p class="review-text">{{ review.comment }}</p>
              </div>
            </div>
          </template>
          <p v-else>Chưa có đánh giá nào.</p>
        </div>
      </div>
    </div>

    <div class="course-right-col">
      <div class="floating-card">
        <div class="card-media">
          <template v-if="course.video_preview_url">
            <video width="100%" controls :src="course.video_preview_url"></video>
          </template>
          <img v-else :src="course.thumbnail || 'https://picsum.photos/300/170'" alt="Thumbnail">
        </div>

        <div class="card-body">
          <div class="price-area">
            <template v-if="hasLearningAccess">
              <i class="bi bi-info-circle-fill"></i> <span class="current-price" style="font-size: 16px;">{{ accessMessage }}</span>
            </template>
            <span v-else class="current-price">{{ course.price.raw === 0 ? 'Miễn phí' : course.price.formatted }}</span>
          </div>

          <button v-if="hasLearningAccess" @click="onGoLearning" class="btn-primary w-100 text-center d-flex justify-content-center align-items-center">
            Tới khóa học
          </button>

          <button v-else @click="onEnroll" class="btn-primary w-100 text-center d-flex justify-content-center align-items-center">
            Đăng ký ngay
          </button>

          <div class="course-includes">
            <h4>Khóa học này bao gồm:</h4>
            <ul>
              <li><i class="bi bi-display"></i> {{ videoCount }} video</li>
              <li><i class="bi bi-file-earmark-text"></i> {{ articleCount }} bài viết</li>
              <li><i class="bi bi-infinity"></i> Quyền truy cập trọn đời</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
    --primary-color: #a435f0;
    --dark-bg: #1c1d1f;
    --text-dark: #1c1d1f;
    --text-gray: #6a6f73;
    --border-color: #d1d7dc;
    --star-color: #e59819;
}

.text-warning { color: var(--star-color); }
.w-100 { width: 100%; }

.course-hero { background-color: #1c1d1f; color: #fff; padding: 40px 0; }
.hero-text-area { max-width: 750px;}
.course-title { font-size: 32px; font-weight: 700; margin-bottom: 15px; line-height: 1.2;  }
.course-meta { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; font-size: 14px; }
.rating-number { color: #fbbc04; font-weight: bold; margin-right: 5px; }
.review-count { color: #c0c4fc; text-decoration: underline; margin-left: 5px; }
.course-creator a { color: #c0c4fc; text-decoration: underline; font-weight: 600; }
.course-updated { margin-top: 10px; font-size: 14px; }

.course-main-layout { display: flex; gap: 50px; padding: 40px 20px; align-items: flex-start; position: relative; }
.course-left-col { flex: 2; min-width: 0; }
.course-right-col { flex: 1; width: 340px; }

.floating-card { background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid var(--border-color); position: sticky; top: 20px; margin-top: -300px; z-index: 10; }
.card-media img, .card-media video { width: 100%; display: block; border-bottom: 1px solid var(--border-color); }
.card-body { padding: 24px; }
.price-area { margin-bottom: 15px; }
.current-price { font-size: 32px; font-weight: 700; color: var(--text-dark); }
.btn-primary { background-color: var(--primary-color); color: #8710d8; border: 1px solid #8710d8; padding: 15px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
.btn-primary:hover { background-color: #8710d8; color: #fff;}
.course-includes { margin-top: 20px; }
.course-includes h4 { font-size: 16px; margin-bottom: 10px; }
.course-includes ul { list-style: none; padding: 0; margin: 0; }
.course-includes li { display: flex; align-items: center; gap: 15px; font-size: 14px; color: var(--text-dark); margin-bottom: 10px; }

.section-box { margin-bottom: 40px; }
.section-box h2 { font-size: 24px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark); }
.objectives-box { border: 1px solid var(--border-color); padding: 24px; background: #f7f9fa; }
.objectives-list { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; list-style: none; padding: 0; margin: 0; }
.objectives-list li { display: flex; gap: 10px; font-size: 14px; }

.curriculum-meta { font-size: 14px; color: var(--text-gray); margin-bottom: 15px; }
.accordion-container { border: 1px solid var(--border-color); }
.accordion-item { border-bottom: 1px solid var(--border-color); }
.accordion-item:last-child { border-bottom: none; }
.accordion-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background-color: #f7f9fa; cursor: pointer; font-weight: 600; list-style: none; }
.accordion-header::-webkit-details-marker { display: none; }
.accordion-stats { font-size: 14px; color: var(--text-gray); font-weight: normal; }
details[open] .toggle-icon { transform: rotate(180deg); transition: transform 0.2s; }
.accordion-body { padding: 0 20px; background: #fff; }
.lesson-list { list-style: none; padding: 0; margin: 0; }
.lesson-list li { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); font-size: 14px; }
.lesson-list li:last-child { border-bottom: none; }
.lesson-icon { color: var(--text-gray); margin-right: 10px; }

.review-item { display: flex; gap: 20px; padding: 20px 0; border-bottom: 1px solid var(--border-color); }
.review-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
.reviewer-name { font-weight: 600; margin-bottom: 5px; font-size: 16px; }
.review-stars { display: flex; align-items: center; gap: 5px; margin-bottom: 10px; font-size: 13px; }
.review-date { color: var(--text-gray); margin-left: 10px; }
.review-text { font-size: 15px; line-height: 1.5; }

@media (max-width: 900px) {
    .course-main-layout { flex-direction: column-reverse; }
    .course-right-col { width: 100%; margin-top: 0; }
    .floating-card { position: relative; top: 0; margin-top: 0; }
    .objectives-list { grid-template-columns: 1fr; }
}
</style>
