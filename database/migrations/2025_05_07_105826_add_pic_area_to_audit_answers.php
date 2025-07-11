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
        Schema::table('audit_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_area')->unique()->nullable();
            $table->foreign('pic_area')->references('id')->on('pic_areas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_answers', function (Blueprint $table) {
            $table->dropForeign(['pic_area']);
            $table->dropColumn('pic_area');
        });
    }
};
