<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_memories', function (Blueprint $table) {
            $table->id();
            $table->string('source_lang', 10);
            $table->string('target_lang', 10);
            $table->text('source_text');
            $table->text('target_text');
            $table->string('source_hash', 64)->index();
            $table->string('context', 100)->nullable();
            $table->unsignedInteger('usage_count')->default(1);
            $table->timestamps();

            $table->unique(['source_hash', 'source_lang', 'target_lang'], 'tm_unique_hash_langs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_memories');
    }
};
