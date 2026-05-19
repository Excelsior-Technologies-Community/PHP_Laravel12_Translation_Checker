<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_scans', function (Blueprint $table) {

            $table->id();

            $table->string('locale');

            $table->integer('missing_count')->default(0);

            $table->decimal('completion',5,2)->default(0);

            $table->timestamp('scanned_at');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_scans');
    }
};