<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_proforma_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('proforma_number', 40)->unique();
            $table->foreignId('lease_contract_id')->constrained('lease_contracts')->cascadeOnDelete();
            $table->string('client_name', 150);
            $table->string('attention', 150)->nullable();
            $table->date('invoice_date');
            $table->text('notes')->nullable();
            $table->json('line_items')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 30)->default('Sent');
            $table->timestamps();
        });

        Schema::create('lease_proforma_invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lease_proforma_invoice_id');
            $table->foreign('lease_proforma_invoice_id', 'lpi_payments_lpi_id_fk')
                ->references('id')->on('lease_proforma_invoices')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('method', 100);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_proforma_invoice_payments');
        Schema::dropIfExists('lease_proforma_invoices');
    }
};
