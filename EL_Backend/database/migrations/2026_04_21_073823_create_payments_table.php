<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Mã đơn hàng nội bộ (vnp_TxnRef)
            $table->string('order_id')->unique();

            // Mã giao dịch từ phía VNPay (vnp_TransactionNo) - có sau khi thanh toán
            $table->string('vnpay_transaction_id')->nullable();

            $table->bigInteger('amount'); // Số tiền (VNĐ)

            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->default('vnpay');

            // Lưu toàn bộ log phản hồi từ VNPay để đối soát khi cần
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        Schema::dropIfExists('payments');
    }
};
