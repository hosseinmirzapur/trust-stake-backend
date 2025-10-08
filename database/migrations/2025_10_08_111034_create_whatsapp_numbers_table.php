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
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->unique()->comment('Virtual mobile number for WhatsApp');
            $table->string('session_id')->unique()->comment('WhatsApp session ID');
            $table->string('name')->nullable()->comment('Friendly name for this number');
            $table->text('description')->nullable()->comment('Description/notes for this number');
            $table->enum('status', ['active', 'inactive', 'connected', 'disconnected', 'error'])->default('inactive')->comment('Current status of the number');
            $table->boolean('is_active')->default(true)->comment('Whether this number is available for use');
            $table->timestamp('connected_at')->nullable()->comment('When the session was successfully connected');
            $table->timestamp('last_used_at')->nullable()->comment('When this number was last used for OTP');
            $table->integer('usage_count')->default(0)->comment('Number of times this number has been used');
            $table->integer('error_count')->default(0)->comment('Number of errors encountered');
            $table->json('settings')->nullable()->comment('Additional settings as JSON');
            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
