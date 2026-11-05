<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of each business's eTIMS config, filled from the
 * settings.etims.updated RabbitMQ event (uzapoint/digitax-client).
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('digitax.table', 'etims_configs');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->boolean('addon_active')->default(false);
            $table->string('environment', 10)->default('test');
            $table->text('api_key')->nullable(); // encrypted with APP_KEY
            $table->string('digitax_business_id')->nullable();
            $table->json('modules')->nullable();
            $table->text('callback_url')->nullable();
            // Event version (epoch ms at settings-ms). Older events are ignored.
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('digitax.table', 'etims_configs'));
    }
};
