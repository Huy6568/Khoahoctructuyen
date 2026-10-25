<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminStatisticController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InstructorStatisticController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;


Route::get('/courses/featured', [CourseController::class, 'home'])->name('courses.featured');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/categories', [CourseController::class, 'categories'])->name('categories.index');

Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{course}/reviews', [CourseController::class, 'reviews'])->name('courses.reviews.index');
Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-learning', [CourseController::class, 'myLearning'])->name('courses.my-learning');
    Route::get('/mylearning', [CourseController::class, 'myLearning'])->name('courses.mylearning');
    Route::get('/enrollments', [CourseController::class, 'myLearning'])->name('enrollments.index');

    Route::get('/courses/{course}/learning', [CourseController::class, 'learning'])->name('courses.learning');
    Route::get('/courses/{course}/learn', [CourseController::class, 'learning'])->name('courses.learn');
    Route::get('/courses/{course}/reviews/me', [CourseController::class, 'myReview'])->name('courses.reviews.me');

    Route::post('/courses/{course}/reviews', [CourseController::class, 'storeReview'])->name('courses.reviews.store');
    Route::patch('/courses/{course}/reviews', [CourseController::class, 'updateReview'])->name('courses.reviews.update');
    Route::delete('/courses/{course}/reviews', [CourseController::class, 'deleteReview'])->name('courses.reviews.destroy');

    Route::get('/instructor/courses', [CourseController::class, 'instructorIndex'])->name('instructor.courses.index');
    Route::post('/instructor/courses', [CourseController::class, 'storeFromInstructorCourses'])->name('instructor.courses.store-builder-compat');
    Route::get('/instructor/courses/{id}', [CourseController::class, 'edit'])->name('instructor.courses.edit-compat');
    Route::get('/instructor/courses/{id}/edit', [CourseController::class, 'edit'])->name('instructor.courses.edit-form');
    Route::get('/instructor/courses/builder', [CourseController::class, 'builder'])->name('instructor.courses.builder');
    Route::get('/instructor/courses/builder/{course}', [CourseController::class, 'builder'])->name('instructor.courses.edit');
    Route::post('/instructor/courses/builder', [CourseController::class, 'saveBuilder'])->name('instructor.courses.save-builder');
    Route::post('/instructor/courses/builder/{course}', [CourseController::class, 'saveBuilder'])->name('instructor.courses.update-builder');

    Route::get('/instructor/courses/curriculum', [CourseController::class, 'curriculum'])->name('instructor.courses.curriculum');
    Route::get('/instructor/courses/{course}/curriculum', [CourseController::class, 'curriculum'])->name('instructor.courses.curriculum-show');
    Route::match(['put', 'patch'], '/instructor/courses/{course}/curriculum', [CourseController::class, 'store'])->name('instructor.courses.update-curriculum');
    Route::get('/instructor/courses/curriculum/{course}', [CourseController::class, 'curriculum'])->name('instructor.courses.edit-curriculum');
    Route::post('/instructor/courses/curriculum', [CourseController::class, 'store'])->name('instructor.courses.store');
    Route::post('/instructor/courses/curriculum/{course}', [CourseController::class, 'store'])->name('instructor.courses.update');

    Route::match(['put', 'patch'], '/instructor/courses/{id}', [CourseController::class, 'update'])->name('instructor.courses.update-info');
    Route::delete('/instructor/courses/{course}', [CourseController::class, 'destroy'])->name('instructor.courses.destroy');
    Route::patch('/instructor/courses/{course}/visibility', [CourseController::class, 'toggleVisibility'])->name('instructor.courses.toggle-visibility');

    Route::get('/admin/users', [AdminController::class, 'user'])->name('admin.user');
    Route::get('/admin/courses', [AdminController::class, 'course'])->name('admin.courses.index');
    Route::get('/admin/categories', [AdminController::class, 'category'])->name('admin.category');
    Route::patch('/admin/courses/{course}/approve', [AdminController::class, 'approveCourse'])->name('admin.courses.approve');
    Route::patch('/admin/courses/{course}/reject', [AdminController::class, 'rejectCourse'])->name('admin.courses.reject');
    Route::patch('/admin/courses/{course}/undo', [AdminController::class, 'undoCourse'])->name('admin.courses.undo');
    Route::patch('/admin/users/{user}/ban', [AdminController::class, 'banUser'])->name('admin.users.ban');
    Route::patch('/admin/users/{user}/unban', [AdminController::class, 'unbanUser'])->name('admin.users.unban');
    Route::patch('/admin/categories/{category}/approve', [AdminController::class, 'approveCategory'])->name('admin.categories.approve');
    Route::patch('/admin/categories/{category}/reject', [AdminController::class, 'rejectCategory'])->name('admin.categories.reject');
    Route::patch('/admin/categories/{category}/undo', [AdminController::class, 'undoCategory'])->name('admin.categories.undo');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
});

// Route hứng kết quả VNPAY trả về (public callback)
Route::get('/checkout/vnpay-return', [CheckoutController::class, 'vnpayReturn'])->name('checkout.vnpayReturn');
Route::get('/checkout/vnpay-ipn', [CheckoutController::class, 'vnpayIpn'])->name('checkout.vnpayIpn');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Route BẮT BUỘC phải có Token mới vào được
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::get('/user/me', [AuthController::class, 'me']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::post('/upload/video', [UploadController::class, 'uploadVideo']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/statistics', [AdminStatisticController::class, 'index']);
});

// Hệ thống hiện tại dùng role user/admin, instructor được xác định theo instructor_id của course.
Route::middleware(['auth:sanctum'])->prefix('instructor')->group(function () {
    Route::get('/statistics', [InstructorStatisticController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::get('/courses/{course}/progress', [LessonProgressController::class, 'show']);
    Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonProgressController::class, 'complete']);
    Route::delete('/courses/{course}/lessons/{lesson}/complete', [LessonProgressController::class, 'destroy']);
});
