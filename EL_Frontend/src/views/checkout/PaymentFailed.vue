<script setup lang="ts">
import { useRoute } from 'vue-router'
const route = useRoute()
// Lấy 'message' và 'code' từ URL Backend trả về
const errorMessage = route.query.message || 'Thanh toán không thành công.'
const errorCode = route.query.code
</script>

<template>
  <div class="payment-error text-center">
    <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
    <h1>Thanh toán thất bại</h1>
    <p>{{ errorMessage }} (Mã lỗi: {{ errorCode }})</p>
    <router-link to="/checkout" class="btn btn-primary">Thử lại</router-link>
  </div>
</template>
<style scoped>
.payment-error {
  padding: 50px 20px;
}
.payment-error h1 {
  margin-top: 20px;
  font-size: 2rem;
}
.payment-error p {
  margin: 20px 0;
  font-size: 1.2rem;
}
.payment-error .btn {
  margin-top: 20px;
}

</style>