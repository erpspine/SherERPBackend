<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ProformaInvoiceController;
use App\Http\Controllers\Api\SafariAllocationController;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProformaAllocationPaymentStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('proforma_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('safari_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('proforma_invoice_id')->nullable();
            $table->unsignedInteger('vehicle_id');
        });
    }

    public function test_allocation_sync_preserves_payments_and_reports_progress(): void
    {
        foreach (['Paid', 'Deposit', 'Confirmed'] as $status) {
            foreach ([0, 1, 3] as $count) {
                DB::table('safari_allocations')->delete();
                $pi = ProformaInvoice::create(['status' => $status]);
                $pi->lead_id = 165;
                $pi->setRelation('lead', new Lead(['no_of_vehicles' => 3]));
                for ($vehicle = 1; $vehicle <= $count; $vehicle++) {
                    DB::table('safari_allocations')->insert([
                        'lead_id' => 165, 'proforma_invoice_id' => $pi->id, 'vehicle_id' => $vehicle,
                    ]);
                }
                // Only status is persisted by these methods; lead_id is fixture context.
                $pi->syncOriginal();
                $expected = $status === 'Confirmed'
                    ? ([0 => 'Confirmed', 1 => 'Partially Allocated', 3 => 'Allocated'][$count])
                    : $status;
                $method = new \ReflectionMethod(ProformaInvoiceController::class, 'syncProformaAllocationStatus');
                $summary = $method->invoke(new ProformaInvoiceController(), $pi);
                $this->assertSame($expected, $pi->fresh()->status);
                $this->assertSame($count, $summary['allocatedVehicles']);
                $this->assertSame([0 => 'none', 1 => 'partial', 3 => 'full'][$count], $summary['allocationProgress']);

                $pi->status = $status;
                $pi->save();
                $method = new \ReflectionMethod(SafariAllocationController::class, 'syncSingleProformaStatus');
                $method->invoke(new SafariAllocationController(), $pi);
                $this->assertSame($expected, $pi->fresh()->status);
            }
        }
    }
}
