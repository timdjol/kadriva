<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->string('age_rating')->nullable()->after('country_code');
        });

        Schema::create('title_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('character')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['title_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_credits');
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn('age_rating');
        });
    }
};
