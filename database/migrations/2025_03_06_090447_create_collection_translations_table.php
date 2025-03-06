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
        Schema::create('collection_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->index();
            $table->string('name')->nullable();
            $table->text('text')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_translations');
    }
};
