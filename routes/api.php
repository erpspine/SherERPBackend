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
    Route::get('/roles', [UserController::class, 'roles'])->middleware('permission:users.view');
    Route::get('/permissions', [UserController::class, 'permissions'])->middleware('permission:users.view');
    Route::get('/roles/permissions', [UserController::class, 'rolePermissions'])->middleware('permission:users.view');
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete');

    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view');
    Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:leads.delete');

    Route::get('/quotations', [QuotationController::class, 'index'])->middleware('permission:quotations.view');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->middleware('permission:quotations.view');
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->middleware('permission:quotations.view');
    Route::post('/quotations/{quotation}/convert-to-pi', [ProformaInvoiceController::class, 'convertFromQuotation'])->middleware('permission:quotations.update');
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('permission:quotations.create');
    Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->middleware('permission:quotations.update');
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->middleware('permission:quotations.delete');

    Route::get('/proforma-invoices', [ProformaInvoiceController::class, 'index'])->middleware('permission:proforma-invoices.view');
    Route::get('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'show'])->middleware('permission:proforma-invoices.view');
    Route::get('/proforma-invoices/{proformaInvoice}/pdf', [ProformaInvoiceController::class, 'pdf'])->middleware('permission:proforma-invoices.view');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:invoices.update');
    Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->middleware('permission:invoices.approve');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete');
    Route::get('/invoice-payments', [InvoiceController::class, 'allPayments'])->middleware('permission:invoice-payments.view');
    Route::get('/invoices/{invoice}/payments', [InvoiceController::class, 'payments'])->middleware('permission:invoice-payments.view');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->middleware('permission:invoice-payments.create');

    // Company settings
    Route::get('/settings/company',  [SettingsController::class, 'showCompany'])->middleware('permission:settings.view');
    Route::put('/settings/company',  [SettingsController::class, 'updateCompany'])->middleware('permission:settings.update');

    // Parks
    Route::get('/parks', [ParkController::class, 'index'])->middleware('permission:parks.view');
    Route::get('/parks/{park}', [ParkController::class, 'show'])->middleware('permission:parks.view');
    Route::post('/parks', [ParkController::class, 'store'])->middleware('permission:parks.create');
    Route::put('/parks/{park}', [ParkController::class, 'update'])->middleware('permission:parks.update');
    Route::delete('/parks/{park}', [ParkController::class, 'destroy'])->middleware('permission:parks.delete');

    // Park Rates
    Route::get('/park-rates', [ParkRateController::class, 'index'])->middleware('permission:park-rates.view');
    Route::get('/park-rates/{parkRate}', [ParkRateController::class, 'show'])->middleware('permission:park-rates.view');
    Route::post('/park-rates', [ParkRateController::class, 'store'])->middleware('permission:park-rates.create');
    Route::put('/park-rates/{parkRate}', [ParkRateController::class, 'update'])->middleware('permission:park-rates.update');
    Route::delete('/park-rates/{parkRate}', [ParkRateController::class, 'destroy'])->middleware('permission:park-rates.delete');
    Route::get('/parks/{park}/rates', [ParkRateController::class, 'byPark'])->middleware('permission:park-rates.view');

    // Concession Rates
    Route::get('/concession-rates', [ConcessionRateController::class, 'index'])->middleware('permission:concession-rates.view');
    Route::get('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'show'])->middleware('permission:concession-rates.view');
    Route::post('/concession-rates', [ConcessionRateController::class, 'store'])->middleware('permission:concession-rates.create');
    Route::put('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'update'])->middleware('permission:concession-rates.update');
    Route::delete('/concession-rates/{concessionRate}', [ConcessionRateController::class, 'destroy'])->middleware('permission:concession-rates.delete');
    Route::get('/parks/{park}/concession-rates', [ConcessionRateController::class, 'byPark'])->middleware('permission:concession-rates.view');

    // Transport Rates
    Route::get('/transport-rates', [TransportRateController::class, 'index'])->middleware('permission:transport-rates.view');
    Route::get('/transport-rates/{transportRate}', [TransportRateController::class, 'show'])->middleware('permission:transport-rates.view');
    Route::post('/transport-rates', [TransportRateController::class, 'store'])->middleware('permission:transport-rates.create');
    Route::put('/transport-rates/{transportRate}', [TransportRateController::class, 'update'])->middleware('permission:transport-rates.update');
    Route::delete('/transport-rates/{transportRate}', [TransportRateController::class, 'destroy'])->middleware('permission:transport-rates.delete');

    // Vehicles
    Route::get('/vehicles', [VehicleController::class, 'index'])->middleware('permission:vehicles.view');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->middleware('permission:vehicles.view');
    Route::post('/vehicles', [VehicleController::class, 'store'])->middleware('permission:vehicles.create');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->middleware('permission:vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->middleware('permission:vehicles.delete');

    // Job Cards
    Route::get('/job-cards', [JobCardController::class, 'index'])->middleware('permission:job-cards.view');
    Route::get('/job-cards/{jobCard}', [JobCardController::class, 'show'])->middleware('permission:job-cards.view');
    Route::get('/job-cards/{jobCard}/pdf', [JobCardController::class, 'pdf'])->middleware('permission:job-cards.view');
    Route::post('/job-cards', [JobCardController::class, 'store'])->middleware('permission:job-cards.create');
    Route::put('/job-cards/{jobCard}', [JobCardController::class, 'update'])->middleware('permission:job-cards.update');
    Route::delete('/job-cards/{jobCard}', [JobCardController::class, 'destroy'])->middleware('permission:job-cards.delete');

    Route::get('/safari-allocations', [SafariAllocationController::class, 'index'])->middleware('permission:safari-allocations.view');
    Route::get('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'show'])->middleware('permission:safari-allocations.view');
    Route::post('/safari-allocations', [SafariAllocationController::class, 'store'])->middleware('permission:safari-allocations.create');
    Route::put('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'update'])->middleware('permission:safari-allocations.update');
    Route::delete('/safari-allocations/{safariAllocation}', [SafariAllocationController::class, 'destroy'])->middleware('permission:safari-allocations.delete');
});
