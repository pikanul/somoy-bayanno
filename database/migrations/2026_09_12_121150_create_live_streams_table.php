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
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title_bn');
            $table->string('slug')->unique();
            $table->string('provider', 64)->default('youtube')->index();
            $table->string('stream_url', 2048);
            $table->string('embed_url', 2048)->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->text('description_bn')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('autoplay')->default(true);
            $table->boolean('muted')->default(true);
            $table->string('status_text')->nullable();
            $table->unsignedInteger('sort_order')->default(100)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};
