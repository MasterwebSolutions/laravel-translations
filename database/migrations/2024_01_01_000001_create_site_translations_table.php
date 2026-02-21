<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_translations', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key', 100);
            $table->string('lang', 10)->index();
            $table->text('value');
            $table->timestamps();

            $table->unique(['group', 'key', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_translations');
    }
};
