<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../../api/axios'

const isLoading = ref(false)
const isUploadingAvatar = ref(false)
const submitError = ref('')
const submitSuccess = ref('')

// Form state
const form = ref({
  fullname: '',
  email: '',
  bio: '',
  avatar: '' // Lưu URL thay vì File
})

const passwordForm = ref({
  old_password: '',
  new_password: '',
  password_confirmation: ''
})

const isChangingPassword = ref(false)

const handleChangePassword = async () => {
  // Validate cơ bản tại frontend
  if (passwordForm.value.new_password !== passwordForm.value.password_confirmation) {
    alert('Mật khẩu mới và xác nhận mật khẩu không khớp!')
    return
  }

  try {
    isChangingPassword.value = true
    await api.patch('/profile/password', passwordForm.value)
    
    alert('Đổi mật khẩu thành công!')
    // Reset form sau khi đổi thành công
    passwordForm.value = {
      old_password: '',
      new_password: '',
      password_confirmation: ''
    }
  } catch (error: any) {
    alert(error.response?.data?.message || 'Mật khẩu cũ không chính xác!')
  } finally {
    isChangingPassword.value = false
  }
}

const avatarPreview = ref('https://placehold.co/200')

// 1. Lấy thông tin user hiện tại khi load trang
const fetchProfile = async () => {
  try {
    isLoading.value = true
    const res = await api.get('/profile')
    const userData = res.data?.data || res.data

    form.value.fullname = userData.fullname || ''
    form.value.email = userData.email || ''
    form.value.bio = userData.bio || ''
    
    if (userData.avatar) {
      form.value.avatar = userData.avatar
      avatarPreview.value = userData.avatar
    }
  } catch (error) {
    console.error('Lỗi tải thông tin cá nhân:', error)
  } finally {
    isLoading.value = false
  }
}

// 2. Tận dụng lại API Upload Local chúng ta đã làm
const handleAvatarChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || !target.files[0]) return

  const file = target.files[0]
  
  // Validate dung lượng (Tối đa 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('Vui lòng chọn ảnh dưới 2MB.')
    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file) // Key 'file' khớp với UploadController

  try {
    isUploadingAvatar.value = true
    const res = await api.post('/upload/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // API trả về URL, cập nhật ngay vào form và preview
    form.value.avatar = res.data.url
    avatarPreview.value = res.data.url
    
  } catch (err) {
    console.error(err)
    alert('Lỗi tải ảnh lên máy chủ!')
  } finally {
    isUploadingAvatar.value = false
    target.value = '' // Reset input
  }
}

// 3. Submit Form lên Backend
const handleSubmit = async () => {
  try {
    submitError.value = ''
    submitSuccess.value = ''
    
    await api.patch('/profile', form.value)
    
    submitSuccess.value = 'Cập nhật thông tin cá nhân thành công!'
    
    // Ẩn thông báo sau 3 giây
    setTimeout(() => { submitSuccess.value = '' }, 3000)
    
  } catch (error: any) {
    console.error(error)
    submitError.value = error.response?.data?.message || 'Có lỗi xảy ra khi lưu dữ liệu.'
  }
}

onMounted(fetchProfile)
</script>

<template>
  <div class="profile-container">
    <div class="header-section mb-4">
      <h2 class="font-weight-bold">Thông tin cá nhân</h2>
      <p class="text-muted">Quản lý thông tin hồ sơ và ảnh đại diện của bạn.</p>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <span class="spinner-border text-primary"></span> Đang tải...
    </div>

    <form v-else @submit.prevent="handleSubmit" class="profile-form">
      <div v-if="submitSuccess" class="alert alert-success">{{ submitSuccess }}</div>
      <div v-if="submitError" class="alert alert-danger">{{ submitError }}</div>

      <div class="form-layout">
        <div class="profile-left">
          <div class="form-row d-flex gap-3 mb-3">
            <div class="form-group flex-grow-1">
              <label class="form-label font-weight-bold">Họ và tên</label>
              <input type="text" v-model="form.fullname" class="form-control" placeholder="Ví dụ: Nguyễn Văn" required>
            </div>
          </div>
          <div class="form-group mb-3">
            <label class="form-label font-weight-bold">Email</label>
            <input type="email" v-model="form.email" class="form-control bg-light" readonly>
            <small class="text-muted mt-1 d-block">Bạn không thể thay đổi địa chỉ email này.</small>
          </div>

          <div class="form-group mb-4">
            <label class="form-label font-weight-bold">Tiểu sử (Bio)</label>
            <textarea v-model="form.bio" class="form-control" rows="6" placeholder="Giới thiệu đôi nét về kinh nghiệm giảng dạy của bạn..."></textarea>
            <!-- <small class="text-muted mt-1 d-block">Tiểu sử này sẽ hiển thị trên trang chủ của khóa học.</small> -->
          </div>

          <div class="form-actions">
            <button type="submit" class="btn px-4 py-2 font-weight-bold">Lưu thay đổi</button>
          </div>

          <hr class="my-5">

            <div class="security-section">
            <h4 class="mb-4 font-weight-bold">Đổi mật khẩu</h4>
            
            <div class="form-group mb-3">
                <label class="form-label font-weight-bold">Mật khẩu hiện tại</label>
                <input type="password" v-model="passwordForm.old_password" class="form-control" placeholder="********">
            </div>

            <div class="form-row d-flex gap-3 mb-3">
                <div class="form-group flex-grow-1">
                <label class="form-label font-weight-bold">Mật khẩu mới</label>
                <input type="password" v-model="passwordForm.new_password" class="form-control" placeholder="Tối thiểu 8 ký tự">
                </div>

                <div class="form-group flex-grow-1">
                <label class="form-label font-weight-bold">Xác nhận mật khẩu mới</label>
                <input type="password" v-model="passwordForm.password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu mới">
                </div>
            </div>

            <div class="form-actions mb-5">
                <button type="button" @click="handleChangePassword" class="btn btn-outline-danger px-4" :disabled="isChangingPassword">
                {{ isChangingPassword ? 'Đang xử lý...' : 'Cập nhật mật khẩu' }}
                </button>
            </div>
            </div>

        </div>

        <div class="profile-right">
          <div class="avatar-upload-box text-center p-4 border rounded bg-light">
            <label class="font-weight-bold d-block mb-3">Ảnh đại diện</label>
            
            <div class="avatar-preview-container mb-3 position-relative d-inline-block">
              <div v-if="isUploadingAvatar" class="upload-overlay">
                <span class="spinner-border text-white"></span>
              </div>
              <img :src="avatarPreview" alt="Avatar" class="avatar-preview-img rounded-circle object-fit-cover" width="150" height="150">
            </div>

            <div class="upload-btn-wrapper position-relative overflow-hidden">
              <button type="button" class="btn btn-outline-dark w-100 font-weight-bold" :disabled="isUploadingAvatar">
                {{ isUploadingAvatar ? 'Đang tải...' : 'Thay đổi ảnh' }}
              </button>
              <input type="file" @change="handleAvatarChange" accept="image/jpeg, image/png, image/webp" class="file-input position-absolute top-0 start-0 opacity-0 w-100 h-100" style="cursor: pointer;" :disabled="isUploadingAvatar">
            </div>
            
            <small class="text-muted d-block mt-2">Hỗ trợ JPG, PNG. Tối đa 2MB.</small>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.profile-container {
  margin: 0 auto;
  padding: 20px;
  max-width: 100%;
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 40px;
  align-items: start;
}

.form-label {
  color: #1f2937;
  margin-bottom: 8px;
}

.form-control {
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 10px 15px;
  transition: border-color 0.2s;
}

.form-control:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.bg-light {
  background-color: #f3f4f6 !important;
  cursor: not-allowed;
}

.avatar-upload-box {
  border: 1px dashed #d1d5db !important;
}

.upload-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.object-fit-cover {
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-primary {
  background-color: #4f46e5;
  border-color: #4f46e5;
}

.btn-primary:hover {
  background-color: #4338ca;
}

@media (max-width: 768px) {
  .form-layout {
    grid-template-columns: 1fr;
  }
  .profile-right {
    order: -1; /* Đưa phần avatar lên trên cùng ở mobile */
  }
}
    
.form-actions > button {
    background-color: #a435f0;
    border-color: #a435f0;
    color: #fff;
}
</style>