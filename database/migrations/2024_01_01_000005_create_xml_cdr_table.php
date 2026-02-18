<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('v_xml_cdr', function (Blueprint $table) {
            $table->uuid('xml_cdr_uuid')->primary();
            $table->uuid('domain_uuid')->index();
            $table->string('direction')->nullable();
            $table->string('caller_id_name')->nullable();
            $table->string('caller_id_number')->nullable();
            $table->string('destination_number')->nullable();
            $table->timestamp('start_stamp')->nullable()->index();
            $table->timestamp('answer_stamp')->nullable();
            $table->timestamp('end_stamp')->nullable();
            $table->integer('duration')->default(0);
            $table->integer('billsec')->default(0);
            $table->string('hangup_cause')->nullable();
            $table->string('uuid')->unique();
            $table->timestamps();
            $table->foreign('domain_uuid')->references('domain_uuid')->on('v_domains')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('v_xml_cdr');
    }
};
