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
        Schema::table('areas', function (Blueprint $table) {
            //
            $table->dropForeign(['pic_area']);
            $table->dropColumn('pic_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            //
            $table->string('pic_area');
            $table->foreign('pic_area')->references('emp_id')->on('karyawans')->onDelete('cascade');
        });
    }
};
