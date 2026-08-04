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
        Schema::create('cipher_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 20);
            $table->string('algorithm', 40);
            $table->string('status', 20)->default('success');
            $table->longText('input_text');
            $table->longText('output_text')->nullable();
            $table->longText('parameters')->nullable();
            $table->char('input_hash', 64)->index();
            $table->char('output_hash', 64)->nullable()->index();
            $table->longText('recovered_key')->nullable();
            $table->double('score')->nullable();
            $table->string('idioma', 20)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->index(['user_id', 'operation', 'created_at']);
            $table->index(['user_id', 'algorithm', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cipher_operations');
    }
};
