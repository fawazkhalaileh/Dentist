<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website media library (public images only). Clinical documents never go
 * here; they use the private `documents` table and disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 30);
            // Optimised WebP versions; the original upload is not kept (it may carry EXIF/GPS data).
            $table->string('path');
            $table->string('thumb_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size_bytes');
            // Alternative text per locale, e.g. {"en": "...", "ar": "..."}.
            $table->json('alt')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
