<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->integer('view_count');
            $table->boolean('featured');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE collections ADD COLUMN frontends JSON DEFAULT (JSON_ARRAY('default'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
