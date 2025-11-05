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
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); 
            $table->string('company_name', 100); 
            $table->string('email', 100)->nullable(); 
            $table->string('phone_number', 15)->nullable(); 
            $table->json('import_errors')->nullable(); 
            $table->boolean('is_duplicate')->default(false); 
            $table->unsignedBigInteger('duplicate_of')->nullable(); 
            $table->string('import_batch')->nullable(); 
            $table->timestamps(); 
            $table->index(['company_name','email','phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
