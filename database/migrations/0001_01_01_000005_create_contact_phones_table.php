<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('phone');
            $table->enum('phone_type', ['mobile', 'work', 'whatsapp'])->default('mobile');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['contact_id', 'phone']); // منع تكرار نفس الرقم لنفس الشخص
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_phones');
    }
};
