<script setup lang="ts">
import { ref, onMounted } from 'vue'
import StatisticsDashboard from '../../components/dashboard/statistics.vue'
import api from '../../api/axios'

type DashboardStats = {
  totalRevenue: number
  totalStudents: number
  totalCourses: number
  monthlyRevenue: number[]
}

type TopCourse = {
  id: string | number
  title: string
  instructorName?: string
  revenue: number
}

type RecentTransaction = {
  id: string | number
  time: string
  user: string
  course: string
}

type DashboardData = {
  stats: DashboardStats
  topCourses: TopCourse[]
  recentTransactions: RecentTransaction[]
}

type ApiMonthlyRevenue = {
  month?: string
  revenue?: number
}

type ApiTopCourse = {
  course_id?: string | number
  title?: string
  revenue?: number
}

type ApiTransaction = {
  id?: string | number
  course?: {
    title?: string
  }
  student?: {
    fullname?: string
  }
  purchased_at?: string
}

type ApiAdminStatistics = {
  data?: {
    revenue?: {
      total?: number
      monthly?: ApiMonthlyRevenue[]
    }
    students?: {
      total?: number
    }
    courses?: {
      total?: number
    }
    top_courses?: ApiTopCourse[]
    transactions?: {
      data?: ApiTransaction[]
    }
  }
}

const createEmptyDashboardData = (): DashboardData => ({
  stats: {
    totalRevenue: 0,
    totalStudents: 0,
    totalCourses: 0,
    monthlyRevenue: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  },
  topCourses: [],
  recentTransactions: []
})

const buildMonthlyRevenue = (monthly: ApiMonthlyRevenue[] | undefined): number[] => {
  const result = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]

  if (!Array.isArray(monthly)) {
    return result
  }

  monthly.forEach((item) => {
    if (!item?.month || typeof item.revenue !== 'number') {
      return
    }

    const monthIndex = Number(item.month.slice(5, 7)) - 1
    if (monthIndex >= 0 && monthIndex <= 11) {
      result[monthIndex] = item.revenue
    }
  })

  return result
}

const formatTransactionTime = (isoDate?: string): string => {
  if (!isoDate) {
    return '--:--'
  }

  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) {
    return '--:--'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const normalizeDashboardData = (payload: unknown): DashboardData => {
  if (!payload || typeof payload !== 'object') {
    return createEmptyDashboardData()
  }

  const response = payload as ApiAdminStatistics
  const raw = response.data
  const fallback = createEmptyDashboardData()

  if (!raw) {
    return fallback
  }

  return {
    stats: {
      totalRevenue: typeof raw.revenue?.total === 'number' ? raw.revenue.total : fallback.stats.totalRevenue,
      totalStudents: typeof raw.students?.total === 'number' ? raw.students.total : fallback.stats.totalStudents,
      totalCourses: typeof raw.courses?.total === 'number' ? raw.courses.total : fallback.stats.totalCourses,
      monthlyRevenue: buildMonthlyRevenue(raw.revenue?.monthly)
    },
    topCourses: Array.isArray(raw.top_courses)
      ? raw.top_courses.map((course) => ({
          id: course.course_id ?? 0,
          title: course.title ?? 'Không có tiêu đề',
          instructorName: '-',
          revenue: typeof course.revenue === 'number' ? course.revenue : 0
        }))
      : fallback.topCourses,
    recentTransactions: Array.isArray(raw.transactions?.data)
      ? raw.transactions.data.map((tx) => ({
          id: tx.id ?? 0,
          time: formatTransactionTime(tx.purchased_at),
          user: tx.student?.fullname ?? 'Học viên',
          course: tx.course?.title ?? 'Khóa học'
        }))
      : fallback.recentTransactions
  }
}

const isLoading = ref(true)
const dashboardData = ref<DashboardData>(createEmptyDashboardData())

const fetchAdminData = async () => {
  try {
    const response = await api.get('/admin/statistics')
    dashboardData.value = normalizeDashboardData(response.data)
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => { fetchAdminData() })
</script>

<template>
  <div v-if="isLoading" class="text-center p-5">Đang tải dữ liệu...</div>
  
  <StatisticsDashboard 
    v-else
    :stats="dashboardData.stats"
    :topCourses="dashboardData.topCourses"
    :recentTransactions="dashboardData.recentTransactions"
    :isAdmin="true" 
  />
  </template>