<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 50)->unique();
            $table->mediumText('value')->nullable();
            $table->boolean('status')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
