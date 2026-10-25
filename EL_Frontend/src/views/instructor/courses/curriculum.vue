<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'

interface LectureContent {
  text?: string
  url?: string
  file?: File | null
}

interface Lecture {
  id: string | number
  title: string
  type: 'video' | 'article'
  content: LectureContent
  isUploading?: boolean 
}

interface Section {
  id: string | number
  title: string
  lectures: Lecture[]
}

const route = useRoute()
const router = useRouter()

// --- STATES ---
const currentCourseId = computed(() => {
  const id = route.params.id
  return typeof id === 'string' && id.trim() ? id : null
})
const isEdit = computed(() => !!currentCourseId.value)
const curriculumData = ref<Section[]>([])
const isLoading = ref(false)

// UI States (Quản lý việc ẩn/hiện form)
const showSectionForm = ref(false)
const newSectionTitle = ref('')
const activeLectureFormId = ref<string | number | null>(null)
const newLectureTitle = ref('')
const newLectureType = ref<'video' | 'article'>('video')
const activeContentId = ref<string | number | null>(null)

// Builder data passed from builder.vue via sessionStorage
const builderData = ref<Record<string, any> | null>(null)

// --- LOGIC HỖ TRỢ ---

const normalizeVideoUrl = (url: string) => {
  if (!url) return ''
  const value = url.trim()
  const embedMatch = value.match(/youtube\.com\/embed\/([a-zA-Z0-9_-]{6,})/i)
  if (embedMatch) return `https://www.youtube.com/embed/${embedMatch[1]}`
  const watchMatch = value.match(/[?&]v=([a-zA-Z0-9_-]{6,})/i)
  if (watchMatch) return `https://www.youtube.com/embed/${watchMatch[1]}`
  const shortMatch = value.match(/youtu\.be\/([a-zA-Z0-9_-]{6,})/i)
  if (shortMatch) return `https://www.youtube.com/embed/${shortMatch[1]}`
  return value
}

const extractCurriculumSections = (payload: unknown) => {
  if (!payload || typeof payload !== 'object') return []

  const source = payload as Record<string, any>
  const draft = source.data?.course ?? source.data ?? source.course ?? source

  let sections = draft?.sections ?? source.data?.sections ?? source.sections ?? []
  if (!Array.isArray(sections) && sections?.sections) {
    sections = sections.sections
  }

  return Array.isArray(sections) ? sections : []
}

// --- ACTIONS ---

const fetchData = async () => {
  if (!isEdit.value) {
    // Nếu tạo mới, mặc định có 1 chương Introduction
    curriculumData.value = [{ id: 'sec_init', title: 'Introduction', lectures: [] }]
    return
  }

  const courseId = currentCourseId.value
  if (!courseId) {
    curriculumData.value = [{ id: 'sec_init', title: 'Introduction', lectures: [] }]
    return
  }

  try {
    isLoading.value = true
    console.log(`[Curriculum] Fetching curriculum data for courseId: ${courseId}`)

    const endpoints = [
      `/instructor/courses/${courseId}/curriculum`,
      `/instructor/courses/${courseId}`,
      `/courses/${courseId}/curriculum`,
      `/courses/${courseId}`,
      `/instructor/courses/${courseId}/edit`,
    ]

    let sections: any[] = []
    let lastError: unknown = null

    for (const endpoint of endpoints) {
      try {
        const response = await api.get(endpoint)
        sections = extractCurriculumSections(response.data)

        if (sections.length > 0) {
          break
        }
      } catch (error) {
        lastError = error
        console.warn(`[Curriculum] Endpoint failed: ${endpoint}`, error)
      }
    }

    if (!sections.length && lastError) {
      throw lastError
    }

    console.log(`[Curriculum] Loaded ${sections.length} sections`)

    // Mapping dữ liệu từ API về cấu trúc state
    curriculumData.value = sections.map((s: any) => ({
      id: s.id ?? 'sec_' + Date.now(),
      title: String(s.title ?? 'Section'),
      lectures: (s.lessons ?? s.lectures ?? []).map((l: any) => ({
        id: l.id ?? 'lec_' + Date.now(),
        title: String(l.title ?? 'Lecture'),
        type: (l.content_type ?? l.type ?? 'video') as 'video' | 'article',
        content: {
          text: String(l.article_content ?? l.content?.text ?? l.text ?? ''),
          url: String(l.video_url ?? l.content?.url ?? l.url ?? ''),
          file: null,
        },
      })),
    }))

    if (curriculumData.value.length === 0) {
      console.warn('[Curriculum] No sections found, adding default Introduction section')
      curriculumData.value.push({ id: 'sec_init', title: 'Introduction', lectures: [] })
    }
  } catch (error) {
    console.error(`[Curriculum] Lỗi khi tải chương trình học (courseId: ${courseId}):`, error)
    // Fallback: tạo section mặc định nếu lỗi
    if (curriculumData.value.length === 0) {
      curriculumData.value.push({ id: 'sec_init', title: 'Introduction', lectures: [] })
    }
  } finally {
    isLoading.value = false
  }
}

const addSection = () => {
  if (!newSectionTitle.value.trim()) return
  curriculumData.value.push({
    id: 'sec_' + Date.now(),
    title: newSectionTitle.value,
    lectures: []
  })
  newSectionTitle.value = ''
  showSectionForm.value = false
}

const addLecture = (sectionId: string | number) => {
  if (!newLectureTitle.value.trim()) return
  const section = curriculumData.value.find(s => s.id === sectionId)
  if (section) {
    section.lectures.push({
      id: 'lec_' + Date.now(),
      title: newLectureTitle.value,
      type: newLectureType.value,
      content: { text: '', url: '', file: null }
    })
  }
  newLectureTitle.value = ''
  activeLectureFormId.value = null
}

const removeSection = (sIndex: number) => {
  if (confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ chương này và tất cả bài học bên trong?')) {
    curriculumData.value.splice(sIndex, 1)
  }
}

// Thêm hàm xóa Lecture
const removeLecture = (sIndex: number, lIndex: number) => {
  if (confirm('Bạn có chắc chắn muốn xóa bài học này?')) {
    curriculumData.value[sIndex].lectures.splice(lIndex, 1)
  }
}

const handleFileUpload = async (e: Event, lecture: Lecture) => {
  const target = e.target as HTMLInputElement
  if (!target.files || !target.files[0]) return

  const file = target.files[0]
  if (file.size > 100 * 1024 * 1024) {
    alert('Dung lượng video quá lớn. Vui lòng upload video < 100MB.')
    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file)

  try {
    lecture.isUploading = true
      const res = await api.post('/upload/video', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        timeout: 120000
      })
    
      // Thành công -> Gắn URL vào ô input text 
      lecture.content.url = res.data.url
      alert('Tải video lên máy chủ thành công!')
    } catch (error) {
      console.error(error)
      alert('Lỗi tải video lên máy chủ.')
    } finally {
      lecture.isUploading = false
      target.value = '' // Reset thẻ input
  }
}

const submitCurriculum = async () => {
  if (curriculumData.value.length === 0) {
    alert('Vui lòng tạo ít nhất 1 chương học!')
    return
  }

  try {
    const formData = new FormData()
    
    // 1. SỬA LẠI CÁC KEY CHO KHỚP 100% VỚI CourseService.php
    const transformedData = curriculumData.value.map(s => ({
      title: s.title,
      lectures: s.lectures.map(l => ({ // Đổi 'lessons' thành 'lectures'
        title: l.title,
        type: l.type, // Đổi 'content_type' thành 'type'
        article_content: l.type === 'article' ? (l.content.text || '') : '',
        video_url: l.type === 'video' ? normalizeVideoUrl(l.content.url || '') : ''
      }))
    }))

    console.log('[Curriculum] Submitting data:', transformedData)

    // 2. KHÔNG ĐƯA VÀO VỎ BỌC { sections: ... } NỮA, TRUYỀN TRỰC TIẾP MẢNG
    formData.append('curriculum_data', JSON.stringify(transformedData))
    
    if (isEdit.value) {
      const courseId = currentCourseId.value
      formData.append('_method', 'PUT')
      await api.post(`/instructor/courses/${courseId}/curriculum`, formData)
    } else {
      // Logic gửi kèm builderData (Giữ nguyên)
      if (builderData.value) {
        formData.append('title', builderData.value.title)
        formData.append('description', builderData.value.description)
        formData.append('objectives', builderData.value.objectives)
        formData.append('requirements', builderData.value.requirements)
        formData.append('category_id', String(builderData.value.category_id))
        formData.append('pricing_model', builderData.value.pricing_model)
        if (builderData.value.pricing_model === 'paid') {
          formData.append('price', String(builderData.value.price))
        }
        if (builderData.value.thumbnail_url) {
          formData.append('thumbnail_url', builderData.value.thumbnail_url)
        }
        if (builderData.value.video_preview_url) {
          formData.append('video_preview_url', builderData.value.video_preview_url)
        }
      }
      await api.post('/instructor/courses/curriculum', formData)
    }

    alert('Cập nhật chương trình học thành công!')
    router.push('/instructor/courses')
  } catch (error) {
    console.error('[Curriculum] Submit error:', error)
    alert('Có lỗi xảy ra khi lưu dữ liệu.')
  }
}

onMounted(fetchData)

onMounted(() => {
  // Retrieve builder data from sessionStorage if creating new course
  if (!isEdit.value) {
    const storedData = sessionStorage.getItem('courseBuilderData')
    if (storedData) {
      try {
        builderData.value = JSON.parse(storedData)
        console.log('[Curriculum] Retrieved builder data from sessionStorage:', builderData.value)
        sessionStorage.removeItem('courseBuilderData') // Clean up after retrieval
      } catch (error) {
        console.error('[Curriculum] Failed to parse builder data:', error)
      }
    }
  }
})

watch(
  () => route.params.id,
  () => {
    curriculumData.value = []
    fetchData()
  }
)
</script>

<template>
  <div class="builder-container">
    <div class="builder-header">
      <h2>Chương trình giảng dạy</h2>
      <p class="text-muted">
        {{ isEdit ? 'Chỉnh sửa chương trình giảng dạy của khóa học.' : 'Tạo các chương, bài giảng và nội dung thực hành.' }}
      </p>
    </div>

    <div v-if="isLoading" class="text-center py-5">Đang tải dữ liệu...</div>

    <div v-else id="sections_container">
      <div v-for="(section, sIndex) in curriculumData" :key="section.id" class="section-box">
        <div class="section-header" style="justify-content: space-between;">
          <div>
            <strong>Section {{ sIndex + 1 }}:</strong> 
            <i class="bi bi-file-earmark-text ml-2"></i> {{ section.title }}
          </div>
          
          <button type="button" class="btn-text text-danger m-0" @click="removeSection(sIndex)">
            <i class="bi bi-trash"></i>
          </button>
        </div>

        <div class="lectures-list">
          <div v-for="(lecture, lIndex) in section.lectures" :key="lecture.id" class="lecture-item-wrapper">
            <div class="lecture-item">
              <div class="lecture-title">
                <i class="bi bi-check-circle-fill text-dark mr-2"></i>
                Lecture {{ lIndex + 1 }}: 
                <i class="bi ml-1" :class="lecture.type === 'video' ? 'bi-play-circle-fill' : 'bi-file-text-fill'"></i> 
                {{ lecture.title }}
                <span class="badge-type">{{ lecture.type }}</span>
              </div>
              
              <div class="d-flex align-items-center gap-2" style="gap: 10px;">
                <button type="button" class="btn-outline-purple-sm" @click="activeContentId = (activeContentId === lecture.id ? null : lecture.id)">
                  <i class="bi bi-plus"></i>
                </button>
                
                <button type="button" class="btn-text text-danger" @click="removeLecture(sIndex, lIndex)" style="padding: 0; margin: 0; font-size: 16px;">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>

            <div v-if="activeContentId === lecture.id" class="content-body" style="padding: 15px; background: #fff; border-top: 1px dashed #d1d7dc;">
              <div v-if="lecture.type === 'article'">
                <label class="font-weight-bold mb-2 d-block">Nội dung bài viết:</label>
                <textarea v-model="lecture.content.text" class="form-control" rows="5" placeholder="Nhập nội dung văn bản..."></textarea>
              </div>

              <div v-else-if="lecture.type === 'video'" class="video-options-wrapper" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="video-url-box">
                  <label class="font-weight-bold mb-2 d-block"><i class="bi bi-link"></i> Nhập URL Video</label>
                  <input type="text" v-model="lecture.content.url" class="form-control" placeholder="https://youtube.com/..." @change="lecture.content.url = normalizeVideoUrl(lecture.content.url || '')">
                </div>
                <div class="video-upload-box" style="border-left: 1px solid #d1d5db; padding-left: 20px;">
                  <label class="font-weight-bold mb-2 d-block"><i class="bi bi-cloud-arrow-up"></i> Hoặc tải Video lên</label>
                  <input type="file" class="form-control" accept="video/mp4,video/webm" @change="e => handleFileUpload(e, lecture)">
                  <small class="text-muted d-block mt-1">Ghi đè URL nếu chọn file.</small>
                </div>
              </div>

              <div class="mt-3 text-right">
                <button type="button" class="btn-purple" style="padding: 5px 15px; font-size: 13px;" @click="activeContentId = null">Hoàn thành</button>
              </div>
            </div>
          </div>
        </div>

        <button v-if="activeLectureFormId !== section.id" type="button" class="btn-outline-purple mt-3" @click="activeLectureFormId = section.id">
          <i class="bi bi-plus"></i> Curriculum item
        </button>

        <div v-else class="builder-form-box mt-3">
          <div class="d-flex align-items-center mb-3">
            <span class="font-weight-bold mr-2">New Lecture:</span>
            <input type="text" v-model="newLectureTitle" class="form-control flex-grow" placeholder="Enter a Title">
          </div>
          <div class="d-flex align-items-center mb-3">
            <span class="mr-2 text-muted">Type:</span>
            <select v-model="newLectureType" class="form-control" style="width: 150px;">
              <option value="video">Video</option>
              <option value="article">Article</option>
            </select>
          </div>
          <div class="form-actions text-right border-top pt-2">
            <button type="button" class="btn-text" @click="activeLectureFormId = null">Cancel</button>
            <button type="button" class="btn-purple" @click="addLecture(section.id)">Add Lecture</button>
          </div>
        </div>
      </div>
    </div>

    <div class="add-section-wrapper mt-3">
      <button v-if="!showSectionForm" type="button" class="btn-outline-purple" @click="showSectionForm = true">
        <i class="bi bi-plus"></i> Section
      </button>

      <div v-else class="builder-form-box">
        <div class="d-flex align-items-center mb-2">
          <span class="font-weight-bold mr-2">New Section:</span>
          <input type="text" v-model="newSectionTitle" class="form-control flex-grow" placeholder="Enter a Title">
        </div>
        <div class="form-actions text-right">
          <button type="button" class="btn-text" @click="showSectionForm = false">Hủy bỏ</button>
          <button type="button" class="btn-purple" @click="addSection">Thêm chương</button>
        </div>
      </div>
    </div>

    <div class="bottom-actions mt-5">
      <button type="button" class="btn-outline-secondary" @click="router.back()">Quay lại</button>
      <button type="button" class="btn-primary-large" @click="submitCurriculum">
        {{ isEdit ? 'Cập nhật' : 'Hoàn thành' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Nhúng CSS từ curriculum-builder.css */
:root { --purple: #5624d0; --purple-hover: #401b9c; --border-color: #d1d7dc; --bg-light: #f7f9fa; --text-dark: #1c1d1f; }
.builder-container {
  width: 70vw;
  margin: 0 auto;
  padding: 40px 20px;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  overflow-x: hidden;
}
.section-box { background-color: #f7f9fa; border: 1px solid #d1d7dc; padding: 15px 20px; margin-bottom: 20px; border-radius: 4px; }
.section-header { font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; }
.lectures-list { background-color: #fff; border: 1px solid #d1d7dc; padding: 10px 15px; }
.lecture-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; }
.lecture-item-wrapper { border-bottom: 1px solid #d1d7dc; }
.lecture-item-wrapper:last-child { border-bottom: none; }
.badge-type { background-color: #e5e7eb; font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 10px; text-transform: uppercase; }
.builder-form-box { background-color: #fff; border: 1px solid #d1d7dc; padding: 15px; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d7dc; outline: none; font-size: 14px; }
.btn-outline-purple { background-color: #fff; color: #5624d0; border: 1px solid #5624d0; padding: 8px 15px; font-weight: bold; cursor: pointer; }
.btn-outline-purple-sm { background-color: #fff; color: #5624d0; border: 1px solid #5624d0; padding: 5px 10px; font-weight: bold; font-size: 13px; border-radius: 4px; cursor: pointer; }
.btn-purple { background-color: #5624d0; color: #fff; border: none; padding: 8px 15px; font-weight: bold; cursor: pointer; }
.btn-text { background: none; border: none; color: #5624d0; font-weight: bold; cursor: pointer; margin-right: 15px; }
.btn-primary-large { background-color: #5624d0; color: #fff; border: none; padding: 15px 30px; font-weight: bold; font-size: 16px; cursor: pointer; float: right; }
.btn-outline-secondary { color: #1c1d1f; text-decoration: none; font-weight: bold; background: none; border: none; cursor: pointer; }
.ml-2 { margin-left: 8px; }
.mr-2 { margin-right: 8px; }
.mb-2 { margin-bottom: 8px; }
.mb-3 { margin-bottom: 16px; }
.mt-3 { margin-top: 16px; }
.mt-5 { margin-top: 40px; }
.d-flex { display: flex; }
.align-items-center { align-items: center; }
.flex-grow { flex-grow: 1; }
.text-right { text-align: right; }
.font-weight-bold { font-weight: bold; }
.text-muted { color: #6a6f73; }

@media (max-width: 992px) {
  .builder-container {
    width: 100%;
    max-height: none;
    overflow: visible;
  }
}
</style>