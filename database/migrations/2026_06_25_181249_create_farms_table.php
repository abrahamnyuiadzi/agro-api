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
    Schema::create('farms', function (Blueprint $table) {
    $table->id();

  


    // propriétaire de l'exploitation
    $table->foreignId('user_id')
          ->constrained()
          ->cascadeOnDelete();


    $table->string('name');


    $table->text('description')
          ->nullable();


    // localisation
    $table->string('location');


    $table->string('city')
          ->nullable();


    $table->string('country')
          ->default('Togo');


    // superficie en hectares
    $table->decimal('surface',8,2)
          ->nullable();


    // type d'exploitation
    $table->enum('type',[
        'crop',
        'livestock',
        'mixed'
    ]);


    // image de l'exploitation
    $table->string('image')
          ->nullable();


    $table->boolean('is_verified')
          ->default(false);


    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
