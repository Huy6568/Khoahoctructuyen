<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AppLayout from './layouts/app.vue'
import InstructorLayout from './layouts/instructor.vue'
import AdminLayout from './layouts/admin.vue'

const route = useRoute()

const currentLayout = computed(() => {
  return route.path.startsWith('/instructor') ? InstructorLayout : route.path.startsWith('/admin') ? AdminLayout : AppLayout;
})
</script>

<template>
  <component :is="currentLayout">
    <router-view />
  </component>
</template>
