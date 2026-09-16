<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('route-hits.connection');
    }

    public function up(): void
    {
        Schema::create('route_hits', function (Blueprint $table) {
            $table->char('id', 40)->primary();
            $table->string('app')->index();
            $table->string('email')->nullable()->index();
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->string('uri');
            $table->string('path', 2048);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_hits');
    }
};
