<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->index();
            // One address part for every language: /about and /de/about.
            $table->string('code');
            $table->json('name');
            $table->json('content')->nullable();
            $table->string('template', 50)->default('default');
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            // Where an imported page came from (a Bitrix file path), so a
            // second import updates it instead of adding a copy.
            $table->string('source')->nullable()->index();
            $table->timestamps();

            $table->index(['site_id', 'parent_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
