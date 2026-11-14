<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('cascade'); // الدواء الأساسي (حقنة ملاريا)
            $table->foreignUuid('accessory_id')->constrained('medicines')->onDelete('restrict'); // الصنف المرافق (سرنجة 5ml)
            $table->integer('quantity')->default(1); // الكمية المرافقة لكل وحدة دواء
            $table->boolean('is_free_with_parent')->default(false); // هل يُعطى الملحق مجاناً أم بسعره المستقل
            $table->boolean('is_required')->default(false); // هل الملحق إجباري أم يمكن للكاشير حذفه
            $table->timestamps();

            $table->unique(['medicine_id', 'accessory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_accessories');
    }
};
