<script setup lang="ts">
type PaymentInfo = {
  courseId: string | number
  price: number | string
  date: string
}

type Props = {
  paymentInfo: PaymentInfo
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'go-course'): void
}>()

const formatPrice = (value: any) => {
  return new Intl.NumberFormat('vi-VN').format(Number(value)) + ' đ'
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('vi-VN')
}
</script>

<template>
  <div class="payment-success-page">
    <div class="success-card">
      <div class="success-icon">
        <i class="bi bi-check-lg"></i>
      </div>

      <h1>Thanh toán thành công!</h1>
      <p class="welcome-msg">Chúc bạn học tốt. Giao dịch của bạn đã được xác nhận.</p>

      <div class="transaction-details">
        <div class="detail-item">
          <span class="label">Mã khóa học:</span>
          <span class="value">#{{ paymentInfo.courseId }}</span>
        </div>
        <div class="detail-item">
          <span class="label">Số tiền:</span>
          <span class="value highlight">{{ formatPrice(paymentInfo.price) }}</span>
        </div>
        <div class="detail-item">
          <span class="label">Thời gian:</span>
          <span class="value">{{ formatDate(paymentInfo.date) }}</span>
        </div>
      </div>

      <div class="actions">
        <button type="button" class="btn-go-course" @click="emit('go-course')">
          Bắt đầu học ngay
        </button>
        <router-link to="/courses" class="btn-back">Quay lại danh sách khóa học</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.payment-success-page {
  min-height: calc(100vh - 72px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: #f7f9fa;
}

.success-card {
  width: 100%;
  max-width: 500px;
  background: #fff;
  border: 1px solid #d1d7dc;
  border-radius: 8px;
  padding: 40px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.success-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 20px;
  border-radius: 50%;
  background: #ecfdf3;
  color: #067647;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
}

h1 { font-size: 24px; font-weight: 700; color: #1c1d1f; margin-bottom: 8px; }
.welcome-msg { color: #6a6f73; margin-bottom: 30px; }

.transaction-details {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 6px;
  margin-bottom: 30px;
  text-align: left;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 14px;
}

.detail-item:last-child { margin-bottom: 0; }
.label { color: #6a6f73; }
.value { font-weight: 700; color: #1c1d1f; }
.highlight { color: #a435f0; }

.actions { display: flex; flex-direction: column; gap: 15px; }

.btn-go-course {
  border: none;
  background: #a435f0;
  color: #fff;
  font-weight: 700;
  padding: 16px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-go-course:hover { background: #8710d8; }
.btn-back { color: #a435f0; text-decoration: none; font-weight: 700; font-size: 14px; }
.btn-back:hover { text-decoration: underline; }
</style>