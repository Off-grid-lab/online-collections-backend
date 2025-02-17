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
        Schema::create('items', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('author');
            $table->integer('date_earliest')->nullable();
            $table->integer('date_latest')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('publish')->default(true);
            $table->string('identifier')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->boolean('free_download')->default(false);
            $table->boolean('has_image')->default(false);
            $table->integer('download_count')->default(0);
            $table->integer('related_work_order')->nullable();
            $table->integer('related_work_total')->nullable();
            $table->foreignId('description_user_id')->nullable()->constrained('users');
            $table->string('contributor')->nullable();
            $table->text('colors')->nullable();
            $table->string('acquisition_date')->nullable();
            $table->double('image_ratio')->nullable();
            $table->string('exhibition')->nullable();
            $table->string('box')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE items ADD COLUMN frontends JSON DEFAULT (JSON_ARRAY('default'))");
        DB::statement('CREATE INDEX items_frontends_index ON items ( (CAST(frontends AS CHAR(32) ARRAY)) )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
