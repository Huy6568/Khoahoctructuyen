<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import RegisterForm from '../../components/auth/RegisterForm.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

// Quản lý trạng thái con mắt của từng ô
const showPassword = ref(false)
const showConfirmPassword = ref(false)

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

const handleRegister = async () => {
  errors.value = {}
  statusMsg.value = ''
  
  // Validate cơ bản ở Frontend (Tùy chọn)
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Mật khẩu nhập lại không khớp.'
    return
  }

  isSubmitting.value = true

  try {
    await authStore.register(form.value)

    const token = authStore.token

    if (token) {
      router.push('/')
      return
    }

    router.push('/login')
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

    statusMsg.value = err.response?.data?.message || 'Đăng ký thất bại. Vui lòng thử lại.'
  } finally {
    isSubmitting.value = false
  }
}

const dismissStatus = () => {
  statusMsg.value = ''
}
</script>

<template>
  <RegisterForm
    :form="form"
    :errors="errors"
    :status-msg="statusMsg"
    :is-submitting="isSubmitting"
    :show-password="showPassword"
    :show-confirm-password="showConfirmPassword"
    @submit="handleRegister"
    @update:show-password="showPassword = $event"
    @update:show-confirm-password="showConfirmPassword = $event"
    @dismiss-status="dismissStatus"
  />
</template>