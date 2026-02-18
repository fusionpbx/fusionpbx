<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('v_extensions', function (Blueprint $table) {
            $table->uuid('extension_uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('extension');
            $table->string('password')->nullable();
            $table->string('enabled')->default('true');
            $table->string('directory_visible')->default('true');
            $table->integer('max_registrations')->default(1);
            $table->timestamps();
            $table->foreign('domain_uuid')->references('domain_uuid')->on('v_domains')->onDelete('cascade');
            $table->index(['domain_uuid', 'extension']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('v_extensions');
    }
};
