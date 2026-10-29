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
        Schema::table('catalog_tonies', function (Blueprint $table) {
            $table->boolean('preorder')->default(false)->after('available');
            $table->date('release_date')->nullable()->after('preorder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_tonies', function (Blueprint $table) {
            $table->dropColumn(['preorder', 'release_date']);
        });
    }
};
