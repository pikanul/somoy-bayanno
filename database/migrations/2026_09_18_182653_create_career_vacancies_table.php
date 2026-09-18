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
        Schema::create('career_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department');
            $table->string('location');
            $table->string('employment_type')->default('Full-time');
            $table->string('status', 40)->default('draft');
            $table->text('summary')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('requirements')->nullable();
            $table->text('qualifications')->nullable();
            $table->string('experience')->nullable();
            $table->text('skills')->nullable();
            $table->text('salary_benefits')->nullable();
            $table->text('application_instructions')->nullable();
            $table->string('application_email')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('application_deadline')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['application_deadline', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_vacancies');
    }
};
