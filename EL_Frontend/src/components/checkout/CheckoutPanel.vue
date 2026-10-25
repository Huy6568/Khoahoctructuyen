<script setup lang="ts">
type Course = {
  id: number
  title: string
  thumbnail_url: string
  price: number | string | { raw?: number | string; formatted?: string }
}

type Props = {
  course: Course
  selectedMethod: string
  isProcessing: boolean
  statusMsg: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:selectedMethod', value: string): void
  (event: 'checkout'): void
}>()

const normalizePrice = (price: Course['price']) => {
  if (typeof price === 'number') {
    return Number.isFinite(price) ? price : 0
  }

  if (typeof price === 'string') {
    const parsed = Number(price)
    return Number.isFinite(parsed) ? parsed : 0
  }

  const raw = Number(price?.raw)
  return Number.isFinite(raw) ? raw : 0
}

const formatPrice = (price: Course['price']) => {
  return new Intl.NumberFormat('vi-VN').format(normalizePrice(price)) + ' đ'
}
</script>

<template>
  <div class="checkout-page">
    <div v-if="statusMsg" class="container mb-3">
      <div class="alert alert-danger" role="alert">
        {{ statusMsg }}
      </div>
    </div>

    <div v-else class="container checkout-container">
      <div class="checkout-left">
        <h1 class="page-title">Thanh toán</h1>

        <div class="checkout-section">
          <h2 class="section-title">Phương thức thanh toán</h2>

          <div class="payment-methods-accordion">
            <label class="payment-option" :class="{ active: selectedMethod === 'vnpay' }">
              <div class="payment-header">
                <input
                  type="radio"
                  :checked="selectedMethod === 'vnpay'"
                  @change="emit('update:selectedMethod', 'vnpay')"
                >
                <div class="payment-brand">
                  <i class="bi bi-qr-code-scan text-primary"></i>
                  <span class="font-weight-bold">Thanh toán qua VNPAY</span>
                </div>
              </div>
              <div v-if="selectedMethod === 'vnpay'" class="payment-body">
                <p class="payment-desc">
                  Bạn sẽ được chuyển hướng đến cổng thanh toán an toàn của VNPAY.
                  Hỗ trợ ATM nội địa, thẻ quốc tế hoặc quét mã QR qua Mobile Banking.
                </p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="checkout-right">
        <div class="summary-card">
          <h2 class="summary-title">Tóm tắt đơn hàng</h2>

          <div class="order-items">
            <div class="order-item">
              <img :src="course.thumbnail_url || 'https://picsum.photos/120/80'" class="item-thumb">
              <div class="item-info">
                <div class="item-title">{{ course.title }}</div>
                <div class="item-price">{{ formatPrice(course.price) }}</div>
              </div>
            </div>
          </div>

          <div class="summary-totals">
            <hr class="summary-divider">
            <div class="total-row final-total">
              <span>Tổng cộng:</span>
              <span>{{ formatPrice(course.price) }}</span>
            </div>
          </div>

          <p class="terms-text">
            Bằng việc hoàn tất giao dịch, bạn đồng ý với Điều khoản dịch vụ của chúng tôi.
          </p>

          <button
            @click="emit('checkout')"
            class="btn-checkout"
            :disabled="isProcessing"
          >
            {{ isProcessing ? 'Đang kết nối...' : 'Hoàn tất thanh toán' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
    --text-dark: #1c1d1f;
    --text-muted: #6a6f73;
    --border-color: #d1d7dc;
    --bg-light: #f7f9fa;
    --primary-color: #a435f0;
    --primary-hover: #8710d8;
}

.checkout-page { padding: 40px 0; min-height: 80vh; background-color: #fff; }
.checkout-container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1.5fr 1fr; gap: 60px; padding: 0 20px; }
.page-title { font-size: 32px; font-weight: 700; margin-bottom: 40px; }
.section-title { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
.payment-methods-accordion { border: 1px solid #d1d7dc; border-radius: 4px; overflow: hidden; }
.payment-option { display: block; border-bottom: 1px solid #d1d7dc; background-color: #f7f9fa; cursor: pointer; transition: background 0.2s; }
.payment-header { padding: 20px; display: flex; align-items: center; gap: 15px; }
.payment-header input[type="radio"] { width: 20px; height: 20px; accent-color: #1c1d1f; }
.payment-brand { display: flex; align-items: center; gap: 10px; font-size: 16px; }
.payment-brand i { font-size: 24px; color: #0056b3; }
.payment-body { padding: 0 20px 20px 50px; background-color: #f7f9fa; }
.payment-desc { font-size: 14px; color: #6a6f73; line-height: 1.5; }
.payment-option.active { background-color: #fff; }
.payment-option.active .payment-body { background-color: #fff; }
.summary-card { position: sticky; top: 20px; background-color: #fff; }
.summary-title { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
.order-item { display: flex; align-items: center; gap: 12px; }
.item-info { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex: 1; }
.item-thumb { width: 68px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d7dc; }
.item-title { font-size: 14px; font-weight: 700; line-height: 1.4; margin-bottom: 0; }
.item-price { font-size: 14px; color: #6a6f73; white-space: nowrap; }
.total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; color: #6a6f73; }
.summary-divider { border: 0; border-top: 1px solid #d1d7dc; margin: 15px 0; }
.final-total { font-size: 20px; font-weight: 700; color: #1c1d1f; }
.btn-checkout { width: 100%; background-color: #a435f0; color: #fff; border: none; padding: 18px; font-size: 18px; font-weight: 700; cursor: pointer; border-radius: 4px; }
.btn-checkout:hover { background-color: #8710d8; }
.btn-checkout:disabled { background-color: #ccc; cursor: not-allowed; }
.money-back-guarantee { text-align: center; font-size: 13px; color: #6a6f73; margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 5px; }

@media (max-width: 900px) {
    .checkout-container { grid-template-columns: 1fr; }
    .checkout-right { order: -1; }
    .summary-card { position: relative; top: 0; }
}
</style>