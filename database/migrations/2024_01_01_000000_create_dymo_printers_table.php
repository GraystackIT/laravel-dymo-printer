<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dymo_printers', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id')->unique()->comment('Device UUID from cookie/session');
            $table->string('printer_name');
            $table->json('device_info')->nullable()->comment('Optional browser/OS metadata');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dymo_printers');
    }
};
