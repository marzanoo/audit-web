<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('audit_answers', function (Blueprint $table) {
            // Hapus foreign key dulu
            $table->dropForeign(['pic_area']);

            // Hapus unique constraint
            $table->dropUnique('audit_answers_pic_area_unique');

            // Tambahkan kembali foreign key tanpa unique constraint
            $table->foreign('pic_area')->references('id')->on('pic_areas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('audit_answers', function (Blueprint $table) {
            // Hapus foreign key yang baru
            $table->dropForeign(['pic_area']);

            // Kembalikan unique constraint
            $table->unique('pic_area', 'audit_answers_pic_area_unique');

            // Tambahkan kembali foreign key
            $table->foreign('pic_area')->references('id')->on('pic_areas')->onDelete('cascade');
        });
    }
};
