<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('branch_id')->constrained();
    $table->foreignId('service_id')->constrained();
    $table->string('fid')->nullable();
    $table->string('number', 8);
    $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('status', ['waiting','called','served','skipped'])->default('waiting');
    $table->timestamp('called_at')->nullable();
    $table->timestamp('served_at')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
