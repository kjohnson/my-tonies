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
        Schema::create('catalog_tonies', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->json('skus');
            $table->string('handle');
            $table->string('title');
            $table->string('category');
            $table->string('series')->nullable();
            $table->json('ages');
            $table->decimal('price', 8, 2);
            $table->string('image', 1024)->nullable();
            $table->boolean('available')->default(true);
            $table->boolean('owned')->default(false);
            $table->date('published_at')->nullable();
            $table->timestamp('wishlisted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('owned_tonies', function (Blueprint $table) {
            $table->id();
            $table->string('cloud_id')->unique();
            $table->string('sales_id')->nullable()->index();
            $table->string('title');
            $table->string('series')->nullable();
            $table->string('image', 1024)->nullable();
            $table->string('household');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owned_tonies');
        Schema::dropIfExists('catalog_tonies');
    }
};
