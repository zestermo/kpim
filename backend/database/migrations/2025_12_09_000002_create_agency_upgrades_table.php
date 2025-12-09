<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('level')->default(0);
            $table->timestamps();

            $table->unique(['player_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_upgrades');
    }
};

