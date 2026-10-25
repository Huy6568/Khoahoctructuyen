<script setup lang="ts">
import { computed } from 'vue'

type Category = {
  id: number
  name: string
}

type Props = {
  categories: Category[]
  selectedCategories: number[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:selectedCategories', value: number[]): void
  (event: 'apply'): void
  (event: 'clear'): void
}>()

const selectedModel = computed({
  get: () => props.selectedCategories,
  set: (value: number[]) => emit('update:selectedCategories', value)
})

const onApply = () => {
  emit('apply')
}

const onClear = () => {
  emit('clear')
}
</script>

<template>
  <aside class="col-lg-3 mb-4 mb-lg-0">
    <div class="card border-0 shadow-sm sticky-lg-top" style="top: 90px; z-index: 1;">
      <div class="card-body">
        <h5 class="fw-bold mb-4">Lọc khóa học</h5>

        <form @submit.prevent="onApply">
          <h6 class="fw-semibold mb-3">Chủ đề</h6>
          <div class="d-flex flex-column gap-2 mb-4">
            <template v-for="category in categories" :key="category.id">
              <label class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :value="category.id"
                  v-model="selectedModel"
                >
                <span class="form-check-label">{{ category.name }}</span>
              </label>
            </template>
          </div>

          <div class="d-grid gap-2 mb-3">
            <button type="submit" class="btn btn-dark btn-sm">Áp dụng</button>
            <button type="button" @click="onClear" class="btn btn-outline-secondary btn-sm">Xóa lọc</button>
          </div>
        </form>
      </div>
    </div>
  </aside>
</template>
