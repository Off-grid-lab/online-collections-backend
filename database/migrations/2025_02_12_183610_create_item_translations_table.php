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
        Schema::create('item_translations', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->index();
            $table->string('locale')->index();
            $table->string('title')->nullable();
            $table->mediumText('description')->nullable();
            $table->string('work_type')->nullable();
            $table->string('work_level')->nullable();
            $table->string('topic')->nullable();
            $table->string('subject')->nullable();
            $table->string('measurement', 512)->nullable();
            $table->string('dating')->nullable();
            $table->string('medium')->nullable();
            $table->string('technique')->nullable();
            $table->string('inscription', 512)->nullable();
            $table->string('place')->nullable();
            $table->string('state_edition')->nullable();
            $table->string('gallery')->nullable();
            $table->string('relationship_type')->nullable();
            $table->string('related_work')->nullable();
            $table->string('description_source')->nullable();
            $table->string('description_source_link')->nullable();
            $table->string('credit')->nullable();
            $table->text('additionals')->nullable();
            $table->string('object_type')->nullable();
            $table->string('style_period')->nullable();
            $table->string('current_location')->nullable();
            $table->unique(['item_id', 'locale']);
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_translations');
    }
};
