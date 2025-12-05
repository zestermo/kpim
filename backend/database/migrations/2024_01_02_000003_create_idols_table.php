<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unsignedTinyInteger('vocal')->default(50);
            $table->unsignedTinyInteger('dance')->default(50);
            $table->unsignedTinyInteger('visual')->default(50);
            $table->unsignedTinyInteger('charm')->default(50);
            $table->unsignedTinyInteger('stamina')->default(50);
            $table->unsignedBigInteger('popularity')->default(0);
            $table->timestamp('training_until')->nullable();
            $table->string('sprite_key')->nullable();
            $table->string('rarity')->default('common');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idols');
    }
};

