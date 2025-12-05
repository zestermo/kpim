<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('idol_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable(); // leader, main vocal, main dancer, etc
            $table->timestamps();

            $table->unique(['group_id', 'idol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};

