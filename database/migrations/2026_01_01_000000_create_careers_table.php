<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('field');
            $table->json('keywords');
            $table->text('description');
            $table->text('courses');
            $table->text('skills');
            $table->text('first_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
