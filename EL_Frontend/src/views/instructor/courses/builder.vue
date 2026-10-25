<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'

interface Category { id: number; name: string }

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const categories = ref<Category[]>([])
const isLoading = ref(false)

// Thêm trạng thái (Loading cờ)
const isUploadingThumbnail = ref(false)
const isUploadingVideo = ref(false)

const form = ref({
  title: '',
  description: '',
  objectives: '',
  requirements: '',
  category_id: '',
  pricing_model: 'free',
  price: '',
  thumbnail_url: '', // Đổi từ File sang String URL
  video_preview_url: '' // Đổi từ File sang String URL
})

const thumbnailPreview = ref('')
const videoPreview = ref('')
const submitError = ref('')

// 3. Lấy dữ liệu (Danh mục & Thông tin khóa học nếu là Edit)
const extractCourseDraft = (payload: unknown) => {
  if (!payload || typeof payload !== 'object') return null
  const source = payload as Record<string, any>
  const draft = source.data?.course ?? source.data ?? source.course ?? source
  return draft && typeof draft === 'object' ? draft : null
}

const populateCourseDraft = (draft: Record<string, any>) => {
  form.value.title = String(draft.title || '')
  form.value.description = String(draft.description || '')
  form.value.objectives = String(draft.objectives || '')
  form.value.requirements = String(draft.requirements || '')
  form.value.category_id = draft.category_id ?? draft.category?.id ?? ''
  form.value.pricing_model = draft.pricing_model || draft.price_model || 'free'
  form.value.price = String(draft.price ?? '')
  
  thumbnailPreview.value = draft.thumbnail_url ?? draft.thumbnail ?? ''
  form.value.thumbnail_url = thumbnailPreview.value
  
  videoPreview.value = draft.video_preview_url ?? ''
  form.value.video_preview_url = videoPreview.value
}

const fetchData = async () => {
  try {
    isLoading.value = true
    
    try {
      const catRes = await api.get('/categories')
      categories.value = catRes.data?.data || catRes.data || []
    } catch (err) {
      console.error('[Builder] Lỗi khi tải danh mục:', err)
      categories.value = []
    }

    if (isEdit.value) {
      const courseId = String(route.params.id)
      const endpoints = [
        `/instructor/courses/${courseId}`,
        `/courses/${courseId}`,
        `/instructor/courses/${courseId}/edit`,
      ]

      let draft: Record<string, any> | null = null
      let lastError: unknown = null

      for (const endpoint of endpoints) {
        try {
          console.log(`[Builder] Fetching course data from: ${endpoint}`)
          const response = await api.get(endpoint)
          draft = extractCourseDraft(response.data)

          if (draft?.title) {
            console.log('[Builder] Course data loaded:', { title: draft.title, id: draft.id })
            break
          }
        } catch (error) {
          lastError = error
          console.warn(`[Builder] Endpoint failed: ${endpoint}`, error)
        }
      }

      if (draft?.title) {
        populateCourseDraft(draft)
      } else {
        console.error(`[Builder] Không tải được dữ liệu khóa học (id: ${courseId})`, lastError)
      }
    }
  } catch (error) {
    console.error('[Builder] Lỗi tổng quát khi tải dữ liệu:', error)
  } finally {
    isLoading.value = false
  }
}

// 4. Xử lý File
const handleFileChange = async (e: Event, type: 'thumbnail' | 'video') => {
  const target = e.target as HTMLInputElement
  if (!target.files || !target.files[0]) return

  const file = target.files[0]
  const formData = new FormData()
  formData.append('file', file) // Key 'file' khớp với UploadController

  if (type === 'thumbnail') {
    try {
      isUploadingThumbnail.value = true
      const res = await api.post('/upload/image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      form.value.thumbnail_url = res.data.url
      thumbnailPreview.value = res.data.url
    } catch (err) {
      alert('Lỗi tải ảnh lên máy chủ!')
    } finally {
      isUploadingThumbnail.value = false
      target.value = '' // Reset input
    }
  } else {
    try {
      isUploadingVideo.value = true
      const res = await api.post('/upload/video', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        timeout: 120000 // Chờ 2 phút cho video nặng
      })
      form.value.video_preview_url = res.data.url
      videoPreview.value = res.data.url
    } catch (err) {
      alert('Lỗi tải video lên máy chủ!')
    } finally {
      isUploadingVideo.value = false
      target.value = ''
    }
  }
}



// 5. Submit Form (Sử dụng FormData vì có upload file)
const handleSubmit = async () => {
  try {
    submitError.value = ''
    // ... Validate các trường y như cũ ...
    const title = form.value.title.trim()
    const categoryId = Number(form.value.category_id)
    if (!title || !categoryId) { submitError.value = 'Vui lòng nhập tiêu đề và chọn danh mục.'; return }

    const formData = new FormData()
    formData.append('title', title)
    formData.append('description', form.value.description.trim())
    formData.append('objectives', form.value.objectives.trim())
    formData.append('requirements', form.value.requirements.trim())
    formData.append('category_id', String(categoryId))
    formData.append('pricing_model', form.value.pricing_model)
    if (form.value.pricing_model === 'paid') formData.append('price', String(form.value.price))

    // CHỈ GỬI STRING URL LÊN BACKEND
    if (form.value.thumbnail_url) formData.append('thumbnail_url', form.value.thumbnail_url)
    if (form.value.video_preview_url) formData.append('video_preview_url', form.value.video_preview_url)

    let courseId: string | number
    if (isEdit.value) {
      formData.append('_method', 'PUT')
      courseId = route.params.id as string
      await api.post(`/instructor/courses/${courseId}`, formData)
    } else {
      const response = await api.post('/instructor/courses', formData)
      console.log('[Builder] Create course response:', response.data)
      console.log('[Builder] Response data.data:', response.data?.data)
      console.log('[Builder] Response data.data.draft:', response.data?.data?.draft)
      
      // Backend saves as draft without creating course yet
      // The course will be created when curriculum is submitted
      // Check for course ID if backend changed behavior
      courseId = 
        response.data?.data?.id ?? 
        response.data?.data?.course?.id ??
        response.data?.data?.draft?.id ??
        response.data?.id ?? 
        response.data?.course?.id
      
      // If no courseId, redirect to curriculum page in "create" mode
      // The curriculum page will create the course when submitted
      if (!courseId) {
        console.log('[Builder] No course ID yet (draft mode). Redirecting to curriculum for new course creation.')
        // Store builder form data in sessionStorage for curriculum page
        sessionStorage.setItem('courseBuilderData', JSON.stringify({
          title: form.value.title,
          description: form.value.description,
          objectives: form.value.objectives,
          requirements: form.value.requirements,
          category_id: form.value.category_id,
          pricing_model: form.value.pricing_model,
          price: form.value.price,
          thumbnail_url: form.value.thumbnail_url,
          video_preview_url: form.value.video_preview_url
        }))
        router.push('/instructor/courses/curriculum')
        return
      }
    }
    router.push(`/instructor/courses/curriculum/${courseId}`)
  } catch (error) {
    console.error(error)
    submitError.value = 'Lỗi khi lưu dữ liệu.'
  }
}

onMounted(fetchData)

watch(
  () => route.params.id,
  () => {
    fetchData()
  }
)
</script>

<template>
  <div class="editor-container">
    <div v-if="isLoading" class="text-center py-5">Đang tải cấu hình editor...</div>
    
    <form v-else @submit.prevent="handleSubmit" class="course-form">
      <div v-if="submitError" class="alert alert-danger mb-3">{{ submitError }}</div>
      <div class="form-layout">
        
        <div class="main-column">
          <div class="form-group">
            <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
            <input 
              type="text" 
              v-model="form.title" 
              class="form-control" 
              placeholder="Nhập tên khóa học của bạn..." 
              required
            >
          </div>

          <div class="form-group">
            <label class="form-label">Mô tả</label>
            <div class="text-editor-wrapper">
              <textarea 
                v-model="form.description" 
                class="editor-textarea" 
                rows="8" 
                placeholder="Nhập mô tả chi tiết cho khóa học..."
              ></textarea>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Bạn sẽ học được gì?</label>
            <p class="help-text">Định nghĩa các kết quả đạt được từ khóa học (Kết thúc bằng ;;).</p>
            <textarea 
              v-model="form.objectives" 
              class="form-control" 
              rows="5" 
              placeholder="Xây dựng ứng dụng web;;&#10;Thành thạo cơ sở dữ liệu;;"
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Yêu cầu</label>
            <p class="help-text">Các yêu cầu đầu vào hoặc hướng dẫn cho học viên (Kết thúc bằng ;;).</p>
            <textarea 
              v-model="form.requirements" 
              class="form-control" 
              rows="4" 
              placeholder="Có máy tính cá nhân;;&#10;Biết lập trình cơ bản;;"
            ></textarea>
          </div>

          <div class="publish-box">
            <button type="submit" class="btn-primary w-100">
              {{ isEdit ? 'Cập nhật khóa học' : 'Tạo và tiếp tục' }}
            </button>
          </div>
        </div>

        <div class="sidebar-column">
          
          <div class="sidebar-box">
            <h4 class="box-title">Ảnh giới thiệu</h4>
            <div class="upload-area">
              <template v-if="isUploadingThumbnail">
                <span class="spinner-border text-primary"></span>
                <div class="mt-2 text-primary">Đang tải lên...</div>
              </template>
              <template v-else-if="thumbnailPreview">
                <img :src="thumbnailPreview" class="preview-img">
              </template>
              <template v-else>
                <i class="bi bi-image upload-icon"></i>
                <div class="upload-text">Tải ảnh lên</div>
                <p class="upload-hint">JPG, PNG. Tối đa 5MB.</p>
              </template>
              <input v-if="!isUploadingThumbnail" type="file" @change="e => handleFileChange(e, 'thumbnail')" accept="image/*" class="file-input">
            </div>
          </div>

          <div class="sidebar-box">
            <h4 class="box-title">Video giới thiệu</h4>
            <div class="upload-area">
              <template v-if="isUploadingVideo">
                <span class="spinner-border text-primary"></span>
                <div class="mt-2 text-primary">Đang tải lên...</div>
              </template>
              <template v-else-if="videoPreview">
                <video :src="videoPreview" class="preview-video" controls></video>
              </template>
              <template v-else>
                <i class="bi bi-camera-video upload-icon"></i>
                <div class="upload-text">Tải video lên</div>
                <p class="upload-hint">MP4, WebM. Tối đa 100MB.</p>
              </template>
              <input v-if="!isUploadingVideo" type="file" @change="e => handleFileChange(e, 'video')" accept="video/*" class="file-input">
            </div>
          </div>

          <div class="sidebar-box">
            <h4 class="box-title">Danh mục <span class="text-danger">*</span></h4>
            <select v-model="form.category_id" class="form-control form-select" required>
              <option value="">-- Chọn danh mục --</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="sidebar-box">
            <h4 class="box-title">Giá bán</h4>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" v-model="form.pricing_model" value="free">
                <span>Miễn phí</span>
              </label>
              <label class="radio-label">
                <input type="radio" v-model="form.pricing_model" value="paid">
                <span>Trả phí</span>
              </label>
            </div>

            <div v-if="form.pricing_model === 'paid'" class="price-input-wrapper mt-3">
              <label class="form-label">Giá bán (VNĐ)</label>
              <input 
                type="number" 
                v-model="form.price" 
                class="form-control" 
                placeholder="VD: 599000"
              >
            </div>
          </div>

        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
/* Nhúng CSS từ course-builder.css */
.editor-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.editor-container {
  max-height: calc(100vh - 110px);
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-gutter: stable;
  scrollbar-width: thin;
  scrollbar-color: #b5b8c8 #eef0f5;
  scroll-behavior: smooth;
}

.editor-container::-webkit-scrollbar {
  width: 10px;
}

.editor-container::-webkit-scrollbar-track {
  background: #eef0f5;
  border-radius: 10px;
}

.editor-container::-webkit-scrollbar-thumb {
  background: #b5b8c8;
  border-radius: 10px;
  border: 2px solid #eef0f5;
}

.editor-container::-webkit-scrollbar-thumb:hover {
  background: #989db0;
}
.form-layout { display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px; align-items: flex-start; }
.form-group { margin-bottom: 24px; }
.form-label { display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937; font-size: 15px; }
.help-text { font-size: 13px; color: #6b7280; margin-top: -4px; margin-bottom: 8px; }

.form-control { 
    width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 6px; 
    font-size: 14px; outline: none; transition: border-color 0.2s; background-color: #fff; 
}
.form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
.text-danger { color: #dc2626; }

.text-editor-wrapper { border: 1px solid #d1d5db; border-radius: 6px; background: #fff; overflow: hidden; }
.editor-textarea { width: 100%; border: none; padding: 16px; outline: none; resize: vertical; font-size: 14px; color: #374151; min-height: 200px;}

.sidebar-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.box-title { font-size: 15px; font-weight: 600; margin-bottom: 15px; color: #1f2937; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px; }

.btn-primary { background-color: #4f46e5; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
.btn-primary:hover { background-color: #4338ca; }
.w-100 { width: 100%; }

/* Upload Area */
.upload-area { border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; background-color: #f9fafb; position: relative; transition: border-color 0.2s; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center;}
.upload-area:hover { border-color: #6366f1; background-color: #eff6ff; }
.upload-icon { font-size: 30px; color: #9ca3af; margin-bottom: 10px; }
.file-input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.preview-img { width: 100%; max-height: 150px; object-fit: cover; border-radius: 4px; }
.preview-video { width: 100%; max-height: 150px; }

.radio-group { display: flex; gap: 20px; }
.radio-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; }

@media (max-width: 992px) { .form-layout { grid-template-columns: 1fr; } }
</style>