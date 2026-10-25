import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import appLayout from "../layouts/app.vue"
import instructorLayout from '../layouts/instructor.vue'
import adminLayout from "../layouts/admin.vue"

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'home',
    component: () => import("../views/home/homepage.vue"),
    meta: {layout: appLayout},
  },

  {
    path: '/instructor/profile',
    name: 'instructorProfile',
    component: () => import("../views/home/profileEdit.vue"),
    meta: {layout: instructorLayout},
  },

  {
    path: '/admin/profile',
    name: 'adminProfile',
    component: () => import("../views/home/profileEdit.vue"),
    meta: {layout: adminLayout},
  },

  {
    path: "/courses",
    name: "courses",
    component: () => import("../views/courses/index.vue"),
    meta: {layout: appLayout},
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/login.vue'),
    meta: {layout: appLayout},
  },

  {
    path: '/register',
    name: 'register',
    component: () => import('../views/auth/register.vue'),
    meta: {layout: appLayout},
  },

  {
    path: '/my-learning',
    name: 'my-learning',
    component: () => import('../views/courses/my-learning.vue'),
    meta: {layout: appLayout},
  },

  {
    path: "/courses/:id",
    name: "course-detail",
    component: () => import("../views/courses/detail.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/courses/:courseId/learn",
    name: "course-learn",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/courses/:courseId/learn/:lessonId",
    name: "course-learn-lesson",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/courses/:courseId/learning",
    name: "course-learning",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/courses/:courseId/learning/:lessonId",
    name: "course-learning-lesson",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/course/:courseId/learn",
    name: "course-learn-alias",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/course/:courseId/learn/:lessonId",
    name: "course-learn-alias-lesson",
    component: () => import("../views/courses/learn.vue"),
    meta: {layout: appLayout},
  },

  {
    path: "/checkout/:id?",
    name: "checkout",
    component: () => import("../views/checkout/index.vue"),
    meta: {layout: appLayout},
  },

  {
    path: '/payment-success',
    name: 'payment-success',
    component: () => import('../views/checkout/PaymentSuccess.vue'),
    meta: { title: 'Thanh toán thành công', layouts: appLayout },
  },

  {
    path: "/admin/statistics",
    name: "adminStatistics",
    component: () => import("../views/admin/statistics.vue"),
    meta: {layout: adminLayout, title: 'Thống kê hệ thống'},
  },

  {
    path: "/admin/users",
    name: "adminUsers",
    component: () => import("../views/admin/user/users.vue"),
    meta: {layout: adminLayout, title: 'Người dùng'},
  },

  {
    path: "/admin/categories",
    name: "adminCategories",
    component: () => import("../views/admin/categories/categories.vue"),
    meta: {layout: adminLayout, title: 'Danh mục'},
  },

  {
    path: "/admin/courses",
    name: "adminCourses",
    component: () => import("../views/admin/courses/courses.vue"),
    meta: {layout: adminLayout, title: 'Khóa học'},
  },

  {
    path: "/instructor/statistics",
    name: "instructorStatistics",
    component: () => import("../views/instructor/statistics.vue"),
    meta: {layout: instructorLayout, title: 'Thống kê cá nhân'},
  },

  {
    path: "/instructor/courses",
    name: "instructor",
    component: () => import("../views/instructor/courses/index.vue"),
    meta: {layout: instructorLayout, title: 'Khóa học của tôi'},
  },

  {
    path: "/instructor/courses/builder",
    name: "instructorBuilder",
    component: () => import("../views/instructor/courses/builder.vue"),
    meta: {layout: instructorLayout, title: 'Trình tạo khóa học'},
  },

  {
    path: "/instructor/courses/builder/:id",
    name: "instructorBuilderEdit",
    component: () => import("../views/instructor/courses/builder.vue"),
    meta: {layout: instructorLayout, title: 'Trình sửa khóa học'},
  },

  {
    path: "/instructor/courses/curriculum",
    name: "instructorCurriculum",
    component: () => import("../views/instructor/courses/curriculum.vue"),
    meta: {layout: instructorLayout, title: 'Chương trình giảng dạy'},
  },

  {
    path: "/instructor/courses/curriculum/:id",
    name: "instructorCurriculumEdit",
    component: () => import("../views/instructor/courses/curriculum.vue"),
    meta: {layout: instructorLayout, title: 'Sửa Chương trình giảng dạy'},
  },

]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
