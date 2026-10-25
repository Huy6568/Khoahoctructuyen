<script setup lang="ts">
type RegisterForm = {
  name: string
  email: string
  password: string
  password_confirmation: string
}

type Props = {
  form: RegisterForm
  errors: Record<string, string>
  statusMsg: string
  isSubmitting: boolean
  showPassword: boolean
  showConfirmPassword: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'submit'): void
  (event: 'update:showPassword', value: boolean): void
  (event: 'update:showConfirmPassword', value: boolean): void
  (event: 'dismiss-status'): void
}>()

const togglePassword = () => emit('update:showPassword', !props.showPassword)
const toggleConfirmPassword = () => emit('update:showConfirmPassword', !props.showConfirmPassword)

const toggleAllPasswords = (event: Event) => {
  const isChecked = (event.target as HTMLInputElement).checked
  emit('update:showPassword', isChecked)
  emit('update:showConfirmPassword', isChecked)
}

</script>

<template>
  <div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 72px); background-color: #f7f9fc;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div v-if="statusMsg" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ statusMsg }}
            <button type="button" class="btn-close" @click="emit('dismiss-status')"></button>
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-body p-5">
              <h1 class="h3 fw-bold text-dark mb-4 text-center">Đăng ký</h1>

              <form @submit.prevent="emit('submit')" novalidate>
                <div class="mb-4">
                  <label for="name" class="form-label fw-500 text-dark">Tên đầy đủ</label>
                  <input
                    id="name"
                    type="text"
                    class="form-control form-control-lg"
                    :class="{ 'is-invalid': errors.name }"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nhập tên đầy đủ của bạn"
                  >
                  <div v-if="errors.name" class="invalid-feedback d-block">
                    {{ errors.name }}
                  </div>
                </div>

                <div class="mb-4">
                  <label for="email" class="form-label fw-500 text-dark">Email</label>
                  <input
                    id="email"
                    type="email"
                    class="form-control form-control-lg"
                    :class="{ 'is-invalid': errors.email }"
                    v-model="form.email"
                    required
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
                      autocomplete="new-password"
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

                <div class="mb-3">
                  <label for="password_confirmation" class="form-label fw-500 text-dark">Nhập lại mật khẩu</label>
                  <div class="input-group">
                    <input
                      id="password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      class="form-control form-control-lg"
                      :class="{ 'is-invalid': errors.password_confirmation }"
                      v-model="form.password_confirmation"
                      required
                      autocomplete="new-password"
                      placeholder="Nhập lại mật khẩu"
                    >
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="toggleConfirmPassword"
                    >
                      <i class="bi" :class="showConfirmPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                  </div>
                  <div v-if="errors.password_confirmation" class="invalid-feedback d-block">
                    {{ errors.password_confirmation }}
                  </div>
                </div>

                <div class="mb-4">
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="showAllPasswordsCheck"
                      @change="toggleAllPasswords"
                      :checked="showPassword && showConfirmPassword"
                    >
                    <label class="form-check-label text-dark" for="showAllPasswordsCheck">
                      Hiện mật khẩu
                    </label>
                  </div>
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold mb-3" :disabled="isSubmitting">
                  {{ isSubmitting ? 'Đang đăng ký...' : 'Đăng ký' }}
                </button>

                <p class="text-center text-gray-600">
                  Đã có tài khoản?
                  <router-link to="/login" class="text-dark fw-bold text-decoration-none">
                    Đăng nhập
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