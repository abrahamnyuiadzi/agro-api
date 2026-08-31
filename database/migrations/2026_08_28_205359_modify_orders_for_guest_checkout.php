<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modifier la table orders pour permettre
     * les commandes sans authentification.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // L'ancien buyer_id devient facultatif
            $table->foreignId('buyer_id')
                ->nullable()
                ->change();

            // Informations de l'acheteur
            $table->string('first_name')->after('buyer_id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->after('last_name');
            $table->string('email')->nullable()->after('phone');

            // Adresse de livraison
            $table->text('address')->after('email');
            $table->string('city')->after('address');
            $table->string('neighborhood')->nullable()->after('city');

            // Note éventuelle du client
            $table->text('note')->nullable()->after('neighborhood');

            // Moyen de paiement
            $table->enum('payment_method', [
                'flooz',
                'tmoney'
            ])->after('note');
        });
    }

    /**
     * Annuler les modifications.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'email',
                'address',
                'city',
                'neighborhood',
                'note',
                'payment_method',
            ]);
        });
    }
};