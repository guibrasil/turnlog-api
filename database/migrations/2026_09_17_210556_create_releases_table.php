<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('discogs_id')->unique();
            $table->string('title');
            $table->string('artist');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('cover_url')->nullable();
            $table->json('raw');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
