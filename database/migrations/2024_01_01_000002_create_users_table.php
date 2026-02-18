<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('v_users', function (Blueprint $table) {
            $table->uuid('user_uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('user_enabled')->default('true');
            $table->string('contact_uuid')->nullable();
            $table->timestamps();
            $table->foreign('domain_uuid')->references('domain_uuid')->on('v_domains')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('v_users');
    }
};
