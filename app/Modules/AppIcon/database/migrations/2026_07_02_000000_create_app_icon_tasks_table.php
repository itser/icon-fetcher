<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_icon_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('bundle_id');
            $table->string('status');
            $table->string('apple_icon_url')->nullable();
            $table->string('google_icon_url')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_icon_tasks');
    }
};
