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
        Schema::create('counters', function (Blueprint $table) {
            $table->id();

            // Counter belongs to a branch
            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            // Counter serves a specific service
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional: staff assigned to this counter
            $table->foreignId('user_id')
                ->nullable() // allow creating counter without staff
                ->constrained()
                ->nullOnDelete();

            // Counter display name
            $table->string('name'); // e.g. "Counter 1"

            // Counter status
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
