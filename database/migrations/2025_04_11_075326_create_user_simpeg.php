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
        Schema::create('user_simpeg', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->foreignUuid('master_opd_id')->nullable();
            $table->foreign('master_opd_id')->references('id')->on('master_opd');

            $table->string('nip', 100)->nullable();
            $table->string('opd_id', 100)->nullable();
            $table->string('opd_nm', 100)->nullable();
            $table->string('sub_opd_id', 100)->nullable();
            $table->string('sub_opd_nm', 100)->nullable();
            $table->string('jns_jbtn_id', 100)->nullable();
            $table->string('jns_jbtn_nm', 100)->nullable();
            $table->string('jabatan_id', 100)->nullable();
            $table->string('jabatan_nm', 100)->nullable();
            $table->string('eselon_id', 100)->nullable();
            $table->string('eselon_nm', 100)->nullable();

            $table->json('json_pegawai')->nullable();
            $table->boolean('is_active');

            
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_simpeg');
    }
};
