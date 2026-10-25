<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../api/axios'
import AdminUserTable from '../../../components/admin/AdminUserTable.vue'

interface User {
    id: number
    fullname: string
    email: string
    avatar?: string
    role: string
    status: 'active' | 'banned' | 'deleted' | string
}

const route = useRoute()
const router = useRouter()

const users = ref<User[]>([])
const isLoading = ref(true)
const meta = ref({ current_page: 1, last_page: 1 })
const searchQuery = ref((route.query.search as string) || '')

const fetchUsers = async () => {
    isLoading.value = true
    try {
        const response = await api.get('/admin/users', {
            params: {
                search: route.query.search,
                page: route.query.page || 1,
            }
        })

        const payload = response.data
        const listData = payload?.data

        if (Array.isArray(listData)) {
            users.value = listData
            meta.value = payload?.meta || { current_page: 1, last_page: 1 }
        } else if (listData && typeof listData === 'object' && Array.isArray(listData.data)) {
            users.value = listData.data
            meta.value = {
                current_page: Number(listData.current_page || 1),
                last_page: Number(listData.last_page || 1),
            }
        } else {
            users.value = []
            meta.value = { current_page: 1, last_page: 1 }
        }
    } catch (error) {
        console.error('Lỗi khi tải danh sách người dùng:', error)
    } finally {
        isLoading.value = false
    }
}

const handleSearch = () => {
    router.push({
        path: '/admin/users',
        query: {
            search: searchQuery.value || undefined,
            page: 1,
        }
    })
}

const changePage = (page: number) => {
    if (page < 1 || page > meta.value.last_page) return
    router.push({
        path: '/admin/users',
        query: { ...route.query, page: page.toString() }
    })
}

const banUser = async (user: User) => {
    if (!window.confirm(`Xác nhận cấm người dùng ${user.fullname}?`)) return
    
    try {
        await api.patch(`/admin/users/${user.id}/ban`)
        await fetchUsers()
    } catch (error) {
        console.error('Lỗi khi cấm người dùng:', error)
        alert('Có lỗi xảy ra khi cấm người dùng.')
    }
}

const unbanUser = async (user: User) => {
    if (!window.confirm(`Xác nhận bỏ cấm người dùng ${user.fullname}?`)) return
    
    try {
        await api.patch(`/admin/users/${user.id}/unban`)
        await fetchUsers()
    } catch (error) {
        console.error('Lỗi khi bỏ cấm người dùng:', error)
        alert('Có lỗi xảy ra khi bỏ cấm người dùng.')
    }
}

onMounted(() => {
    fetchUsers()
})

watch(() => route.query, () => {
    fetchUsers()
}, { deep: true })
</script>

<template>
    <AdminUserTable
      :users="users"
      :is-loading="isLoading"
            :meta="meta"
      :search-query="searchQuery"
      @update:search-query="searchQuery = $event"
      @search="handleSearch"
      @ban="banUser"
      @unban="unbanUser"
            @change-page="changePage"
    />
</template>
