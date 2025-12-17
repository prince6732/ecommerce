<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('sku')->unique();
            $table->decimal('mrp', 7, 2);
            $table->decimal('sp', 7, 2);
            $table->decimal('bp', 7, 2);
            $table->integer('stock')->default(0);
            $table->string('image_url')->nullable();
            $table->json('image_json')->nullable();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->boolean('status')->default(true);
             $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
