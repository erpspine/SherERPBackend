<?php

use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ParkController;
use App\Http\Controllers\Api\ParkRateController;
use App\Http\Controllers\Api\ConcessionRateController;
use App\Http\Controllers\Api\TransportRateController;
use App\Http\Controllers\Api\ProformaInvoiceController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobCardController;
use App\Http\Controllers\Api\SafariAllocationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClientController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserController::class, 'login']);
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view');
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::put('/leads/{lead}', [LeadController::class, 'update']);
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);

    Route::get('/quotations', [QuotationController::class, 'index']);
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show']);
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf']);
    Route::post('/quotations/{quotation}/convert-to-pi', [ProformaInvoiceController::class, 'convertFromQuotation']);
    Route::post('/quotations', [QuotationController::class, 'store']);
    Route::put('/quotations/{quotation}', [QuotationController::class, 'update']);
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);

    Route::get('/proforma-invoices', [ProformaInvoiceController::class, 'index']);
    Route::get('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'show']);
    Route::get('/proforma-invoices/{proformaInvoice}/pdf', [ProformaInvoiceController::class, 'pdf']);

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete');
    Route::get('/invoice-payments', [InvoiceController::class, 'allPayments'])->middleware('permission:invoice-payments.view');
    Route::get('/invoices/{invoice}/payments', [InvoiceController::class, 'payments'])->middleware('permission:invoice-payments.view');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->middleware('permission:invoice-payments.create');

    // Company settings
    Route::get('/settings/company',  [SettingsController::class, 'showCompany'])->middleware('permission:settings.view');
    Route::put('/settings/company',  [SettingsController::class, 'updateCompany'])->middleware('permission:settings.update');

    // Parks
    Route::get('/parks', [ParkController::class, 'index']);
    Route::get('/parks/{park}', [ParkController::class, 'show']);
    Route::post('/parks', [ParkController::class, 'store']);
    Route::put('/parks/{park}', [ParkController::class, 'update']);
    Route::delete('/parks/{park}', [ParkController::class, 'destroy']);

    // Park Rates
    Route::get('/park-rates', [ParkRateController::class, 'index']);
    Route::get('/park-rates/{parkRate}', [ParkRateController::class, 'show']);
    Route::post('/park-rates', [ParkRateController::class, 'store']);
    Route::put('/park-rates/{parkRate}', [ParkRateController::class, 'update']);
    Route::delete('/park-rates/{parkRate}', [ParkRateController::class, 'destroy']);
    Route::get('/parks/{park}/rates', [ParkRateController::class, 'byPark']);

    // Concession Rates
    Route::get('/concession-rates', [ConcessionRateController::class, 'index']);
    Route::get('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'show']);
    Route::post('/concession-rates', [ConcessionRateController::class, 'store']);
    Route::put('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'update']);
    Route::delete('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'destroy']);
    Route::get('/parks/{park}/concession-rates', [ConcessionRateController::class, 'byPark']);

    // Transport Rates
    Route::get('/transport-rates', [TransportRateController::class, 'index']);
    Route::get('/transport-rates/{transportRate}', [TransportRateController::class, 'show']);
    Route::post('/transport-rates', [TransportRateController::class, 'store']);
    Route::put('/transport-rates/{transportRate}', [TransportRateController::class, 'update']);
    Route::delete('/transport-rates/{transportRate}', [TransportRateController::class, 'destroy']);

    // Vehicles
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);

    // Job Cards
    Route::get('/job-cards', [JobCardController::class, 'index']);
    Route::get('/job-cards/{jobCard}', [JobCardController::class, 'show']);
    Route::get('/job-cards/{jobCard}/pdf', [JobCardController::class, 'pdf']);
    Route::post('/job-cards', [JobCardController::class, 'store']);
    Route::put('/job-cards/{jobCard}', [JobCardController::class, 'update']);
    Route::delete('/job-cards/{jobCard}', [JobCardController::class, 'destroy']);

    Route::get('/safari-allocations', [SafariAllocationController::class, 'index']);
    Route::get('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'show']);
    Route::post('/safari-allocations', [SafariAllocationController::class, 'store']);
    Route::put('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'update']);
    Route::delete('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'destroy']);
});
