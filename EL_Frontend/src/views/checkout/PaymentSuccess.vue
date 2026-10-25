<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import PaymentSuccessCard from '../../components/checkout/PaymentSuccessCard.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const normalizeQueryValue = (value: string | string[] | number | undefined): string => {
  if (Array.isArray(value)) {
    return String(value[0] ?? '')
  }

  return String(value ?? '')
}

// 1. Trích xuất dữ liệu từ Query Parameters do Backend gửi về
const paymentInfo = ref({
  courseId: normalizeQueryValue(route.query.course_id as string | string[] | number | undefined),
  price: normalizeQueryValue((route.query.purchase_price || route.query.price) as string | string[] | number | undefined) || '0',
  date: normalizeQueryValue((route.query.purchased_at || route.query.at) as string | string[] | number | undefined)
})

const goToCourseDetail = () => {
  if (paymentInfo.value.courseId) {
    // Thêm query refresh=1 vào URL
    router.replace({
      path: `/courses/${paymentInfo.value.courseId}`,
      query: { refresh: '1' }
    })
    return
  }
  router.replace('/courses')
}

onMounted(() => {
  // Giữ lại trang lâu hơn một chút (3-5 giây) để người dùng kịp xem thông báo
  // Refetch user auth state to ensure enrollment is verified
  authStore.fetchUser().catch(() => {
    console.log('User fetch skipped')
  })
  
  // Auto redirect after 2 seconds to give backend time to process enrollment
  window.setTimeout(goToCourseDetail, 2000)
})
</script>

<template>
  <PaymentSuccessCard
    :payment-info="paymentInfo"
    @go-course="goToCourseDetail"
  />
</template>
