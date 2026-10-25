<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/axios'
import { useAuthStore } from '../../stores/auth'
import CheckoutPanel from '../../components/checkout/CheckoutPanel.vue'

// 1. Định nghĩa Type cho khóa học
interface Course {
    id: number
    title: string
    thumbnail_url: string
    price: number | string | { raw?: number | string; formatted?: string }
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// 2. States
const course = ref<Course | null>(null)
const selectedMethod = ref('vnpay') // Mặc định chọn VNPay
const isLoading = ref(true)
const isProcessing = ref(false)
const statusMsg = ref('')

const resolveCourseId = () => {
    const idFromParam = route.params.id
    if (typeof idFromParam === 'string' && idFromParam.trim()) {
        return idFromParam
    }

    const idFromQuery = route.query.course_id
    if (typeof idFromQuery === 'string' && idFromQuery.trim()) {
        return idFromQuery
    }

    return ''
}

// 3. Lấy thông tin khóa học từ ID trên URL (param: /checkout/:id hoặc query: ?course_id=...)
const fetchCourseInfo = async () => {
    const courseId = resolveCourseId()
    if (!courseId) {
        router.push('/courses')
        return
    }

    try {
        isLoading.value = true
        // Gọi API lấy thông tin khóa học
        const response = await api.get(`/courses/${courseId}`)
        course.value = response.data.data.course
    } catch (error) {
        console.error("Lỗi khi tải thông tin thanh toán:", error)
        router.push('/courses')
    } finally {
        isLoading.value = false
    }
}

// 5. Xử lý thanh toán
const resolvePaymentUrl = (responseData: any) => {
    const payload = responseData?.data ?? responseData ?? {}
    return (
        payload.payment_url ||
        payload.redirect_url ||
        payload.url ||
        responseData?.payment_url ||
        responseData?.redirect_url ||
        responseData?.url ||
        ''
    )
}

const handleCheckout = async () => {
    if (!course.value) return

    try {
        isProcessing.value = true
        statusMsg.value = ''
        // Gửi yêu cầu thanh toán lên Laravel
        const response = await api.post('/checkout/process', {
            course_id: course.value.id,
            payment_method: selectedMethod.value
        })

        const paymentUrl = resolvePaymentUrl(response.data)

        // Backend Laravel sẽ trả về URL của cổng thanh toán (VNPay)
        if (paymentUrl) {
            window.location.href = paymentUrl
            return
        }

        statusMsg.value = 'Không lấy được liên kết thanh toán từ hệ thống. Vui lòng thử lại.'
    } catch (error) {
        statusMsg.value = 'Có lỗi xảy ra trong quá trình khởi tạo thanh toán.'
    } finally {
        isProcessing.value = false
    }
}

onMounted(() => {
    // Kiểm tra đăng nhập trước khi cho phép thanh toán
    if (!authStore.isAuthenticated) {
        router.push('/login')
        return
    }
    fetchCourseInfo()
})
</script>

<template>
    <div class="checkout-page">
        <div v-if="isLoading" class="container text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Đang chuẩn bị đơn hàng...</p>
        </div>

        <CheckoutPanel
          v-else-if="course"
          :course="course"
          v-model:selected-method="selectedMethod"
          :is-processing="isProcessing"
          :status-msg="statusMsg"
          @checkout="handleCheckout"
        />
    </div>
</template>