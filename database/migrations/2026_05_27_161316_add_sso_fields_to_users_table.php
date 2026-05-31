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
        Schema::table('users', function (Blueprint $table) {
            // 1. Menambahkan kolom SSO setelah kolom email
            $table->string('sso_provider')->nullable()->after('email');
            $table->string('sso_id')->nullable()->after('sso_provider');

            // 2. Mengubah kolom password bawaan Laravel menjadi nullable
            $table->string('password')->nullable()->change();

            // 3. Menambahkan index agar pencarian data saat callback SSO lebih cepat
            $table->index(['sso_provider', 'sso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Hapus index terlebih dahulu
            $table->dropIndex(['sso_provider', 'sso_id']);

            // 2. Hapus kolom yang sebelumnya ditambahkan
            $table->dropColumn(['sso_provider', 'sso_id']);

            // 3. Kembalikan kolom password menjadi NOT NULL (wajib diisi)
            $table->string('password')->nullable(false)->change();
        });
    }
};
