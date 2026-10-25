<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import LoginForm from '../../components/auth/LoginForm.vue'

// Khởi tạo router để chuyển trang sau khi login
const router = useRouter()
const authStore = useAuthStore()

// Trạng thái Form
const form = ref({
  email: '',
  password: ''
})

// Trạng thái hiển thị mật khẩu
const showPassword = ref(false)

// Trạng thái lỗi (Mock - Trong thực tế lấy từ API response)
const errors = ref<Record<string, string>>({})
const statusMsg = ref('')
const isSubmitting = ref(false)

const normalizeValidationErrors = (rawErrors: unknown): Record<string, string> => {
  if (!rawErrors || typeof rawErrors !== 'object') {
    return {}
  }

  const normalized: Record<string, string> = {}

  for (const [field, value] of Object.entries(rawErrors as Record<string, unknown>)) {
    if (Array.isArray(value)) {
      normalized[field] = String(value[0] ?? '')
      continue
    }

    if (typeof value === 'string') {
      normalized[field] = value
    }
  }

  return normalized
}

// Hàm xử lý Đăng nhập
const handleLogin = async () => {
  // Reset lỗi trước khi submit
  errors.value = {}
  statusMsg.value = ''
  isSubmitting.value = true
  
  try {
    await authStore.login(form.value)

    router.push('/')
  } catch (error: unknown) {
    const err = error as {
      response?: {
        data?: {
          message?: string
          errors?: unknown
        }
      }
    }

    const validationErrors = normalizeValidationErrors(err.response?.data?.errors)

    if (Object.keys(validationErrors).length > 0) {
      errors.value = validationErrors
      return
    }

    statusMsg.value = err.response?.data?.message || 'Đăng nhập thất bại. Vui lòng thử lại.'
  } finally {
    isSubmitting.value = false
  }
}

const dismissStatus = () => {
  statusMsg.value = ''
}
</script>

<template>
  <LoginForm
    :form="form"
    :errors="errors"
    :status-msg="statusMsg"
    :is-submitting="isSubmitting"
    :show-password="showPassword"
    @submit="handleLogin"
    @update:show-password="showPassword = $event"
    @dismiss-status="dismissStatus"
  />
</template>