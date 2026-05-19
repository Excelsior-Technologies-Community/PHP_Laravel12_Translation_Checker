<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('translation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('locale');
            $table->string('group');
            $table->string('key');
            $table->text('value')->nullable();
            $table->text('missing_translation')->nullable();
            $table->boolean('is_missing')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            
            $table->unique(['locale', 'group', 'key']);
            $table->index(['locale', 'is_missing']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('translation_cache');
    }
};