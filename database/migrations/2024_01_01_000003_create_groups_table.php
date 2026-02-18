<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('v_groups', function (Blueprint $table) {
            $table->uuid('group_uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('group_name');
            $table->text('group_description')->nullable();
            $table->integer('group_level')->default(0);
            $table->string('group_protected')->default('false');
            $table->timestamps();
            $table->foreign('domain_uuid')->references('domain_uuid')->on('v_domains')->onDelete('cascade');
        });
        
        Schema::create('v_user_groups', function (Blueprint $table) {
            $table->uuid('user_group_uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->uuid('user_uuid')->index();
            $table->uuid('group_uuid')->index();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('v_user_groups');
        Schema::dropIfExists('v_groups');
    }
};
