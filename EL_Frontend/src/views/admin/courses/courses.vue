<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'
import AdminCourseTable from '../../../components/admin/AdminCourseTable.vue'

interface Course {
  id: number
  title: string
  instructor: {
    fullname?: string
    name?: string
    full_name?: string
  } | null
  created_at: string
  status: 'pending' | 'published' | 'rejected' | string
}

const route = useRoute()
const router = useRouter()

const courses = ref<Course[]>([])
const isLoading = ref(true)
const meta = ref({ current_page: 1, last_page: 1 })

const searchQuery = ref((route.query.search as string) || '')
const statusFilter = ref((route.query.status as string) || '')

const fetchCourses = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/admin/courses', {
      params: {
        search: route.query.search,
        status: route.query.status,
        page: route.query.page || 1,
      }
    })

    const payload = response.data
    const listData = payload?.data

    if (Array.isArray(listData)) {
      courses.value = listData
      meta.value = payload?.meta || { current_page: 1, last_page: 1 }
    } else if (listData && typeof listData === 'object' && Array.isArray(listData.data)) {
      courses.value = listData.data
      meta.value = {
        current_page: Number(listData.current_page || 1),
        last_page: Number(listData.last_page || 1),
      }
    } else {
      courses.value = []
      meta.value = { current_page: 1, last_page: 1 }
    }
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
  } finally {
    isLoading.value = false
  }
}

const applyFilters = () => {
  router.push({
    path: '/admin/courses',
    query: {
      search: searchQuery.value || undefined,
      status: statusFilter.value || undefined,
      page: 1,
    }
  })
}

const changePage = (page: number) => {
  if (page < 1 || page > meta.value.last_page) return
  router.push({
    path: '/admin/courses',
    query: { ...route.query, page: page.toString() }
  })
}

const updateStatus = async (id: number, action: 'approve' | 'reject' | 'undo') => {
  const confirmMsg = {
    approve: 'Xác nhận duyệt khóa học này?',
    reject: 'Xác nhận từ chối khóa học này?',
    undo: 'Xác nhận hoàn tác trạng thái khóa học này?'
  }

  if (!window.confirm(confirmMsg[action])) return

  try {
    await api.patch(`/admin/courses/${id}/${action}`)
    await fetchCourses()
  } catch (error) {
    alert('Có lỗi xảy ra.')
  }
}

onMounted(fetchCourses)
watch(() => route.query, fetchCourses, { deep: true })
</script>

<template>
  <AdminCourseTable
    :courses="courses"
    :is-loading="isLoading"
    :meta="meta"
    :search-query="searchQuery"
    :status-filter="statusFilter"
    @update:search-query="searchQuery = $event"
    @update:status-filter="statusFilter = $event"
    @apply-filters="applyFilters"
    @view-course="(id) => router.push(`/courses/${id}`)"
    @approve="(id) => updateStatus(id, 'approve')"
    @reject="(id) => updateStatus(id, 'reject')"
    @undo="(id) => updateStatus(id, 'undo')"
    @change-page="changePage"
  />
</template>
