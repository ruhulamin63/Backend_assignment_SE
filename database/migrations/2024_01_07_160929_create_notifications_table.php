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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->CascadeOnDelete();
            $table->enum('platform', ['all', 'web', 'app'])->default('all')->index();
            $table->string('title')->nullable();
            $table->string('app_title')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->text('web_url')->nullable();
            $table->text('app_url')->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('read_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
