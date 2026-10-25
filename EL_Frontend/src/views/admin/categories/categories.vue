<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'
import AdminCategoryTable from '../../../components/admin/AdminCategoryTable.vue'

interface Category {
  id: number
  name: string
  status: 'pending' | 'accepted' | 'rejected' | string
}

const route = useRoute()
const router = useRouter()

const categories = ref<Category[]>([])
const isLoading = ref(true)
const meta = ref({ current_page: 1, last_page: 1 })

const searchQuery = ref((route.query.search as string) || '')
const statusFilter = ref((route.query.status as string) || '')

const fetchCategories = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/admin/categories', {
      params: {
        search: route.query.search,
        status: route.query.status,
        page: route.query.page || 1,
      }
    })

    const payload = response.data
    const listData = payload?.data

    if (Array.isArray(listData)) {
      categories.value = listData
      meta.value = payload?.meta || { current_page: 1, last_page: 1 }
    } else if (listData && typeof listData === 'object' && Array.isArray(listData.data)) {
      categories.value = listData.data
      meta.value = {
        current_page: Number(listData.current_page || 1),
        last_page: Number(listData.last_page || 1),
      }
    } else {
      categories.value = []
      meta.value = { current_page: 1, last_page: 1 }
    }
  } catch (error) {
    console.error('Lỗi khi tải danh mục:', error)
  } finally {
    isLoading.value = false
  }
}

const applyFilters = () => {
  router.push({
    path: '/admin/categories',
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
    path: '/admin/categories',
    query: { ...route.query, page: page.toString() }
  })
}

const updateStatus = async (id: number, action: 'approve' | 'reject' | 'undo') => {
  const confirmMsg = {
    approve: 'Xác nhận duyệt danh mục này?',
    reject: 'Xác nhận từ chối danh mục này?',
    undo: 'Xác nhận hoàn tác trạng thái danh mục này?'
  }

  if (!window.confirm(confirmMsg[action])) return

  try {
    await api.patch(`/admin/categories/${id}/${action}`)
    await fetchCategories()
  } catch (error) {
    alert('Có lỗi xảy ra khi thực hiện thao tác.')
  }
}

onMounted(fetchCategories)
watch(() => route.query, fetchCategories, { deep: true })
</script>

<template>
  <AdminCategoryTable
    :categories="categories"
    :is-loading="isLoading"
    :meta="meta"
    :search-query="searchQuery"
    :status-filter="statusFilter"
    @update:search-query="searchQuery = $event"
    @update:status-filter="statusFilter = $event"
    @apply-filters="applyFilters"
    @approve="(id) => updateStatus(id, 'approve')"
    @reject="(id) => updateStatus(id, 'reject')"
    @undo="(id) => updateStatus(id, 'undo')"
    @change-page="changePage"
  />
</template>

<style scoped>
.admin-container { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); padding: 24px; border: 1px solid #e5e7eb; }
.admin-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
.search-form { display: flex; flex: 1; max-width: 400px; }
.form-control { padding: 10px 16px; border: 1px solid #d1d5db; outline: none; font-size: 14px; border-radius: 6px; transition: border-color 0.2s; }
.search-form input { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; width: 100%; }
.search-form input:focus { border-color: #6366f1; }
.btn-search { background-color: #1c1d1f; color: #fff; border: none; padding: 0 20px; cursor: pointer; transition: background 0.2s; }
.btn-search:hover { background-color: #333; }
.table-responsive { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.admin-table th, .admin-table td { padding: 16px; border-bottom: 1px solid #e5e7eb; text-align: left; }
.admin-table th { background-color: #f9fafb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 12px; }
.badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
.badge-primary { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-success { background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.badge-danger  { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.action-buttons { display: flex; justify-content: center; gap: 8px; }
.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; font-size: 16px; }
.btn-accept { color: #16a34a; }
.btn-accept:hover { background-color: #dcfce7; }
.btn-reject { color: #dc2626; }
.btn-reject:hover { background-color: #fee2e2; }
</style>