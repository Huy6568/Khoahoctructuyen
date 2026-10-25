<script setup lang="ts">
type Props = {
  currentPage: number
  lastPage: number
}

defineProps<Props>()

const emit = defineEmits<{
  (event: 'changePage', page: number): void
}>()
</script>

<template>
  <div v-if="lastPage > 1" class="pagination-container">
    <button
      :disabled="currentPage === 1"
      @click="emit('changePage', currentPage - 1)"
      class="btn-page"
      type="button"
    >
      Trước
    </button>
    <span class="mx-3">Trang {{ currentPage }} / {{ lastPage }}</span>
    <button
      :disabled="currentPage === lastPage"
      @click="emit('changePage', currentPage + 1)"
      class="btn-page"
      type="button"
    >
      Sau
    </button>
  </div>
</template>

<style scoped>
.pagination-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-page {
  min-width: 72px;
  height: 36px;
  border: 1px solid #d1d7dc;
  background: #fff;
  cursor: pointer;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
