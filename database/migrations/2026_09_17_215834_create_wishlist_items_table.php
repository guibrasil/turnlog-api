<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wishlist_id', 'release_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
