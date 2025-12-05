<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('concept')->default('cute');
            $table->unsignedBigInteger('popularity')->default(0);
            $table->date('debut_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};

