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
        Schema::table('authors', function (Blueprint $table) {
            $table->string('address')->nullable()->after('phone');
            $table->unsignedTinyInteger('organization_level')->default(3)->after('featured')->index();
            $table->unsignedInteger('sort_order')->default(100)->after('organization_level')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn(['address', 'organization_level', 'sort_order']);
        });
    }
};
