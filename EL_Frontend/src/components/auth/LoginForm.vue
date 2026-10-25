<script setup lang="ts">
type LoginForm = {
  email: string
  password: string
}

type Props = {
  form: LoginForm
  errors: Record<string, string>
  statusMsg: string
  isSubmitting: boolean
  showPassword: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'submit'): void
  (event: 'update:showPassword', value: boolean): void
  (event: 'dismiss-status'): void
}>()

const togglePassword = () => {
  emit('update:showPassword', !props.showPassword)
}

const onDismissStatus = () => {
  emit('dismiss-status')
}
</script>

<template>
  <div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 72px); background-color: #f7f9fc;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

          <div v-if="statusMsg" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ statusMsg }}
            <button type="button" class="btn-close" @click="onDismissStatus"></button>
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-body p-5">
              <h1 class="h3 fw-bold text-dark mb-4 text-center">Đăng nhập</h1>

              <form @submit.prevent="emit('submit')" novalidate>
                <div class="mb-4">
                  <label for="email" class="form-label fw-500 text-dark">Email</label>
                  <input
                    id="email"
                    type="email"
                    class="form-control form-control-lg"
                    :class="{ 'is-invalid': errors.email }"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Nhập email của bạn"
                  >
                  <div v-if="errors.email" class="invalid-feedback d-block">
                    {{ errors.email }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label fw-500 text-dark">Mật khẩu</label>
                  <div class="input-group">
                    <input
                      id="password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control form-control-lg"
                      :class="{ 'is-invalid': errors.password }"
                      v-model="form.password"
                      required
                      autocomplete="current-password"
                      placeholder="Nhập mật khẩu"
                    >
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="togglePassword"
                    >
                      <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                  </div>
                  <div v-if="errors.password" class="invalid-feedback d-block">
                    {{ errors.password }}
                  </div>
                </div>

                <div class="mb-4">
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="showPasswordCheck"
                      :checked="showPassword"
                      @change="togglePassword"
                    >
                    <label class="form-check-label text-dark" for="showPasswordCheck">
                      Hiện mật khẩu
                    </label>
                  </div>
                </div>

                <div class="mb-4 text-center">
                  <router-link to="/forgot-password" class="text-danger text-decoration-none fw-500 small">
                    Quên mật khẩu?
                  </router-link>
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold mb-3" :disabled="isSubmitting">
                  {{ isSubmitting ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                </button>

                <p class="text-center text-gray-600">
                  Chưa có tài khoản?
                  <router-link to="/register" class="text-dark fw-bold text-decoration-none">
                    Đăng ký ngay
                  </router-link>
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>