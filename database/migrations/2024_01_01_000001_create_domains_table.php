<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('v_domains', function (Blueprint $table) {
            $table->uuid('domain_uuid')->primary();
            $table->uuid('domain_parent_uuid')->nullable()->index();
            $table->string('domain_name')->unique();
            $table->string('domain_enabled')->default('true');
            $table->text('domain_description')->nullable();
            $table->timestamps();
            
            $table->foreign('domain_parent_uuid')
                  ->references('domain_uuid')
                  ->on('v_domains')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v_domains');
    }
};
