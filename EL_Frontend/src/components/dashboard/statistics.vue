<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

type DashboardStats = {
  totalRevenue: number
  totalStudents: number
  totalCourses: number
  monthlyRevenue?: number[]
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

type Props = {
  stats: DashboardStats
  topCourses: TopCourse[]
  recentTransactions: RecentTransaction[]
  isAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false
})

const chartRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: any = null

const initChart = () => {
  if (!chartRef.value) return
  
  if (chartInstance) chartInstance.destroy() // Xóa chart cũ nếu có dữ liệu mới

  chartInstance = new Chart(chartRef.value, {
    type: 'line', // Đổi sang biểu đồ đường cho đẹp
    data: {
      labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
      datasets: [{
        label: 'Doanh thu',
        data: props.stats.monthlyRevenue || [0,0,0,0,0,0,0,0,0,0,0,0],
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } }
    }
  })
}

const formatVND = (val: number) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val)
}

onMounted(() => { initChart() })

watch(
  () => props.stats.monthlyRevenue,
  () => {
    initChart()
  },
  { deep: true }
)
</script>

<template>
  <div class="stats-container">
    <!--<div class="header-flex">
      <h1 class="page-title">
        {{ isAdmin ? 'Thống kê hệ thống' : 'Thống kê' }}
      </h1>
      
    </div>-->

    <div class="kpi-grid">
      <div class="kpi-card">
        <span class="label">Tổng doanh thu</span>
        <div class="value">{{ formatVND(stats.totalRevenue) }}</div>
      </div>
      <div class="kpi-card">
        <span class="label">Tổng học viên</span>
        <div class="value">{{ stats.totalStudents }}</div>
      </div>
      <div class="kpi-card">
        <span class="label">Tổng khóa học</span>
        <div class="value">{{ stats.totalCourses }}</div>
      </div>
      
      <!-- <div v-if="isAdmin" class="kpi-card highlight">
        <span class="label">Lợi nhuận sàn (30%)</span>
        <div class="value text-green">{{ formatVND(stats.totalRevenue * 0.3) }}</div>
      </div> -->
    </div>

    <div class="chart-section">
      <h2 class="section-title">Biểu đồ tăng trưởng (2026)</h2>
      <div class="chart-wrapper">
        <canvas ref="chartRef"></canvas>
      </div>
    </div>

    <div class="data-details">
      <div class="details-card">
        <h2 class="section-title">Top Khóa học</h2>
        <div class="table-scroll">
          <table class="simple-table">
            <thead>
              <tr>
                <th>Hạng</th>
                <th>Khóa học</th>
                <th v-if="isAdmin">Giảng viên</th> <th class="text-right">Doanh thu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(course, index) in topCourses" :key="course.id">
                <td><strong>#{{ index + 1 }}</strong></td>
                <td>{{ course.title }}</td>
                <td v-if="isAdmin" class="text-muted">{{ course.instructorName }}</td>
                <td class="text-right text-primary font-bold">{{ formatVND(course.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="details-card">
        <h2 class="section-title">Giao dịch gần đây</h2>
        <div class="transaction-list">
          <div v-for="tx in recentTransactions" :key="tx.id" class="tx-item">
            <span class="tx-time">{{ tx.time }}</span>
            <span class="tx-content"><strong>{{ tx.user }}</strong> mua "{{ tx.course }}"</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* CSS cơ bản giữ cho gọn, bạn có thể bê CSS từ mockup trước vào đây */
.stats-container { padding: 24px; background: #f9fafb; min-height: 100vh; }
.header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
.badge-admin { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: bold; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
.kpi-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; }
.kpi-card.highlight { background: #f0fdf4; border-color: #bbf7d0; }
.kpi-card .label { font-size: 13px; color: #6b7280; text-transform: uppercase; font-weight: 600; }
.kpi-card .value { font-size: 24px; font-weight: 700; margin-top: 8px; }
.text-green { color: #16a34a; }
.text-primary { color: #4f46e5; }
.font-bold { font-weight: bold; }
.chart-section, .details-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 24px; }
.chart-wrapper { height: 300px; }
.data-details { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.table-scroll { width: 100%; overflow-x: auto; }
.simple-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.simple-table th, .simple-table td { padding: 12px 8px; border-bottom: 1px solid #f3f4f6; text-align: left; }
.text-right { text-align: right !important; }
.text-muted { color: #6b7280; }
.transaction-list { max-height: 320px; overflow-y: auto; padding-right: 4px; }
.tx-item { padding: 12px 0; border-bottom: 1px dashed #e5e7eb; font-size: 14px; display: flex; gap: 12px; }
.tx-time { color: #9ca3af; }
</style>