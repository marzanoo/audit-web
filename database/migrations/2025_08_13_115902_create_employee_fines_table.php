<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_fines', function (Blueprint $table) {
            $table->id();
            $table->string('emp_id')->nullable();
            $table->foreign('emp_id')->references('emp_id')->on('karyawans')->onDelete('cascade');
            $table->foreignId('audit_answer_id')->nullable()->constrained('audit_answers')->onDelete('set null');
            $table->foreignId('detail_audit_answer_id')->nullable()->constrained('detail_audit_answers')->onDelete('set null');
            $table->enum('type', ['fine', 'payment']);
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_fines');
    }
};
