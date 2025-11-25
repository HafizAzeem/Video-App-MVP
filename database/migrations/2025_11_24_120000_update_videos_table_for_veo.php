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
        Schema::table('videos', function (Blueprint $table) {
            $table->text('prompt')->nullable()->after('summary_text');
            $table->string('provider')->nullable()->after('status');
            $table->string('mode')->nullable()->after('provider');
            $table->unsignedTinyInteger('progress')->default(0)->after('mode');
            $table->string('operation_name')->nullable()->after('progress');
            $table->string('storage_uri')->nullable()->after('operation_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'prompt',
                'provider',
                'mode',
                'progress',
                'operation_name',
                'storage_uri',
            ]);
        });
    }
};


