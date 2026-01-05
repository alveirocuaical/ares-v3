<?php



use Illuminate\Support\Facades\Route;

use Modules\Finance\Http\Controllers\IncomeController;
use Modules\Finance\Http\Controllers\UnpaidController;
use Modules\Sale\Http\Controllers\RemissionController;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Inventory\Http\Controllers\MovesController;
use App\Http\Controllers\Tenant\src\Http\ItemsController;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Sale\Http\Controllers\RemissionPaymentController;
use App\Http\Controllers\Tenant\src\Http\Controllers\TaxController;
use App\Http\Controllers\Tenant\src\Http\Controllers\CustomerController;
use App\Http\Controllers\Tenant\src\Http\Controllers\DocumentPosController;
use App\Http\Controllers\Tenant\src\Http\Controllers\PaymentMethodController;

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if ($hostname) {
    Route::domain($hostname->fqdn)->group(function() {

        Route::post('login', 'Api\Tenant\AuthController@login');
        Route::post('loginRes', 'Api\Tenant\LoginResController@login');
        Route::get('/restaurant/list-waiter', 'Tenant\WaiterController@listRecords');

        Route::middleware(['auth:api', 'locked.tenant'])->group(function() {

            Route::prefix('restaurant')->group(function () {
                Route::get('/items', 'Tenant\RestaurantController@items');
                Route::post('/items/price', 'Tenant\RestaurantController@savePrice');

                Route::get('/categories', 'Tenant\RestaurantController@categories');
                Route::get('/configurations', 'Tenant\RestaurantConfigurationController@record');
                Route::get('/waiters', 'Tenant\WaiterController@records');
                Route::get('/tablesAndEnv', 'Tenant\RestaurantConfigurationController@tablesAndEnv');
                Route::post('/table/{id}', 'Tenant\RestaurantConfigurationController@saveTable');
                Route::get('/table/{id}', 'Tenant\RestaurantConfigurationController@getTable');
                Route::get('/notes', 'Tenant\NotesController@records');
                Route::get('/available-sellers', 'Tenant\RestaurantConfigurationController@getSellers');
                Route::get('/correct_pin_check/{id}/{pin}', 'Tenant\RestaurantConfigurationController@correctPinCheck');
                Route::post('/label-table/save', 'Tenant\RestaurantConfigurationController@saveLabelTable');
                Route::post('/command-item/save', 'Tenant\RestaurantItemOrderStatusController@saveItemOrder');
                Route::get('/command-status/items/{id}', 'Tenant\RestaurantItemOrderStatusController@getStatusItems');
                Route::get('/command-status/set/{id}', 'Tenant\RestaurantItemOrderStatusController@setStatusItem');
            });
            //Company
            Route::get('company', 'Tenant\Api\CompanyController@record');
            //Caja
            Route::post('cash/restaurant', 'Api\Tenant\CashController@storeRestaurant');
            Route::post('cash/cash_document', 'Api\Tenant\CashController@cash_document');
            Route::get('cash/opening_cash', 'Api\Tenant\CashController@opening_cash');
            Route::get('cash/opening_cash_check/{cash_id}', 'Api\Tenant\CashController@opening_cash_check');
            Route::get('cash/available-restaurant', 'Api\Tenant\CashController@cash_available');
            Route::post('cash/open', 'Tenant\CashController@store');
            Route::get('cash/close/{cash}', 'Api\Tenant\CashController@close');
            //Facturacion
            //Route::post('co-documents', 'Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController@store');
            //MOBILE
            Route::get('document/series', 'Tenant\Api\MobileController@getSeries');
            Route::get('document/tables', 'Tenant\Api\MobileController@tables');
            Route::get('document/customers', 'Tenant\Api\MobileController@customers');
            Route::post('document/email', 'Tenant\Api\MobileController@document_email');
            Route::post('sale-note', 'Tenant\Api\SaleNoteController@store');
            Route::get('sale-note/series', 'Tenant\Api\SaleNoteController@series');
            Route::get('sale-note/lists', 'Tenant\Api\SaleNoteController@lists');
            Route::post('item', 'Tenant\Api\MobileController@item');
            Route::post('person', 'Tenant\Api\MobileController@person');
            Route::get('document/search-items', 'Tenant\Api\MobileController@searchItems');
            Route::get('document/search-customers', 'Tenant\Api\MobileController@searchCustomers');

            Route::post('documents', 'Tenant\Api\DocumentController@store');
            Route::get('documents/lists', 'Tenant\Api\DocumentController@lists');
            Route::post('summaries', 'Tenant\Api\SummaryController@store');
            Route::post('voided', 'Tenant\Api\VoidedController@store');
            Route::post('retentions', 'Tenant\Api\RetentionController@store');
            Route::post('dispatches', 'Tenant\Api\DispatchController@store');
            Route::post('documents/send', 'Tenant\Api\DocumentController@send');
            Route::post('summaries/status', 'Tenant\Api\SummaryController@status');
            Route::post('voided/status', 'Tenant\Api\VoidedController@status');
            Route::get('services/ruc/{number}', 'Tenant\Api\ServiceController@ruc');
            Route::get('services/dni/{number}', 'Tenant\Api\ServiceController@dni');
            Route::post('services/consult_cdr_status', 'Tenant\Api\ServiceController@consultCdrStatus');
            Route::post('perceptions', 'Tenant\Api\PerceptionController@store');

            Route::post('documents_server', 'Tenant\Api\DocumentController@storeServer');
            Route::get('document_check_server/{external_id}', 'Tenant\Api\DocumentController@documentCheckServer');

            //* New routes DP
            Route::get('items/tables', 'Tenant\ItemController@tables');

            //* POS
            Route::post('document-pos', 'Tenant\DocumentPosController@store');
            Route::get('document-pos/record/{salenote}', 'Tenant\DocumentPosController@record');
            Route::get('document-pos/print/{external_id}/{format?}', 'Tenant\DocumentPosController@toPrint');
            Route::get('document-pos/{id}', [DocumentPosController::class, 'getDocumentPosBasicDetails']);

            Route::get('document-pos-data/{id}',[DocumentPosController::class, 'finDocumentPos']); //* New route DP id
            Route::get('document-pos-data/{id}/payments', [DocumentPosController::class, 'getDocumentPosPayments']);
            Route::post('document-pos-data/payments',[DocumentPosController::class, 'addPayment']);
            Route::post('document-pos-vfp', [DocumentPosController::class, 'store']); //* New route DP VFP
            Route::post('document-pos/email', 'Tenant\DocumentPosController@email');

            //* Items
            Route::get('items/last-code', [ItemsController::class, 'findLastCodeItem']);
            Route::get('items/search', [ItemsController::class, 'search_items']);
            Route::get('items', [ItemsController::class, 'getAllItems']);
            Route::get('items/columns', 'Tenant\ItemController@columns');
            Route::get('items/records', 'Tenant\ItemController@records');
            Route::get('items/tables', 'Tenant\ItemController@tables');
            Route::get('items/record/{item}', 'Tenant\ItemController@record');
            Route::get('pos/search_items', 'Tenant\PosController@search_items');
            Route::get('pos/search_latest_items', 'Tenant\src\Http\ItemsController@getLatestSoldItems');

            //* Customers
            Route::get('customers/{documetType}/{documentNumber}', [CustomerController::class, 'getCustomerByDocument']);
            Route::post('persons', 'Tenant\PersonController@store');
            Route::get('customers-csv', [CustomerController::class, 'getAllCustomersCsv']);

            //* Remissions
            Route::prefix('co-remissions')->group(function() {

                Route::get('', [RemissionController::class, 'idex'])->name('tenant.co-remissions.index');
                Route::post('', [RemissionController:: class, 'store']);
                Route::get('columns', [RemissionController::class, 'columns']);
                Route::get('records', [RemissionController::class, 'records']);
                Route::get('record/{id}', [RemissionController::class,'record']);
                Route::get('create/{id?}', [RemissionController::class, 'create'])->name('tenant.co-remissions.create');
                Route::get('tables', [RemissionController::class,'tables']);
                Route::get('item/tables', [RemissionController::class,'item_tables']);
                Route::get('download/{external_id}/{format?}', [RemissionController::class,'download']);
                Route::get('print/{external_id}/{format?}', [RemissionController::class, 'toPrint']);
                Route::get('voided/{id}', [RemissionController::class,'voided']); // <-- Añadido

            });
            Route::prefix('co-remission-payments')->group(function() {

                Route::get('records/{remission_id}', [RemissionPaymentController::class,  'records']);
                Route::get('remission/{remission_id}', [RemissionPaymentController::class, 'remission']);
                Route::get('tables', [RemissionPaymentController::class ,'tables']);
                Route::post('', [RemissionPaymentController::class, 'store']);
                Route::delete('{remission_payment}', [RemissionPaymentController::class, 'destroy']);

            });

            //* Unpaid Documents
            Route::post('unpaid/records', [UnpaidController::class, 'records']);

            //* Income
            Route::prefix('income')->group(function () {

                    Route::get('', [IncomeController::class, 'index'])->name('tenant.finances.income.index');
                    Route::get('columns', [IncomeController::class, 'columns']);
                    Route::get('records', [IncomeController::class, 'records']);
                    Route::get('records/income-payments/{record}', [IncomeController::class, 'recordsIncomePayments']);
                    Route::get('create', [IncomeController::class, 'create'])->name('tenant.income.create');
                    Route::get('tables', [IncomeController::class, 'tables']);
                    Route::get('table/{table}', [IncomeController::class, 'table']);
                    Route::post('', [IncomeController::class, 'store']);
                    Route::get('record/{record}', [IncomeController::class, 'record']);
                    Route::get('voided/{record}', [IncomeController::class, 'voided']);

                });

            //* Expenses
            Route::prefix('expenses')->group(function () {

                Route::get('', [ExpenseController::class, 'index'])->name('tenant.expenses.index');
                Route::get('columns', [ExpenseController::class, 'columns']);
                Route::get('records', [ExpenseController::class, 'records']);
                Route::get('records/expense-payments/{expense}', [ExpenseController::class, 'recordsExpensePayments']);
                Route::get('create', [ExpenseController::class, 'create'])->name('tenant.expenses.create');
                Route::get('tables', [ExpenseController::class, 'tables']);
                Route::get('table/{table}', [ExpenseController::class, 'table']);
                Route::post('', [ExpenseController::class, 'store']);
                Route::get('record/{expense}', [ExpenseController::class, 'record']);
                Route::get('{record}/voided', [ExpenseController::class, 'voided']);

            });

            //* Taxes
            Route::get('taxes/csv', [TaxController::class, 'getAllTaxes']);
            Route::get('taxes/types/csv', [TaxController::class, 'getAllTaxeTypes']);

            //* Payment Methods
            Route::get('payment-methods/csv', [PaymentMethodController::class, 'getAllPaymentMethods']);

            //* Inventory
            Route::prefix('inventory')->group(function () {
                Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
                Route::get('records', [InventoryController::class, 'records']);
                Route::get('columns', [InventoryController::class, 'columns']);
                Route::get('tables', [InventoryController::class, 'tables']);
                Route::get('tables/transaction/{type}', [InventoryController::class, 'tables_transaction']);
                Route::get('record/{inventory}', [InventoryController::class, 'record']);
                Route::post('/', [InventoryController::class, 'store']);
                Route::post('/transaction', [InventoryController::class, 'store_transaction']);
                Route::post('/transaction/import/input', [InventoryController::class, 'transactionImport']);
                Route::post('move', [InventoryController::class, 'move']);
                Route::get('moves', [MovesController::class, 'index'])->name('inventory.moves.index');
                Route::post('remove', [InventoryController::class, 'remove']);
                Route::get('initialize', [InventoryController::class, 'initialize']);
                Route::get('download', [InventoryController::class, 'download']);
                Route::get('search-items', [InventoryController::class, 'searchItems']);
                Route::post('/transaction/import/massive', [InventoryController::class, 'transactionImportMassive']);
                Route::get('/formats/Format_massive.xlsx', [InventoryController::class, 'downloadFormatMassive']);
            });

        });

        Route::get('documents/search/customers', 'Tenant\DocumentController@searchCustomers');

        Route::post('services/validate_cpe', 'Tenant\Api\ServiceController@validateCpe');
        Route::post('services/consult_status', 'Tenant\Api\ServiceController@consultStatus');
        Route::post('documents/status', 'Tenant\Api\ServiceController@documentStatus');

        Route::get('sendserver/{document_id}/{query?}', 'Tenant\DocumentController@sendServer');



    });
}else{
    Route::domain(env('APP_URL_BASE'))->group(function() {

        //reseller
        Route::post('reseller/detail', 'System\Api\ResellerController@resellerDetail');
        Route::post('reseller/lockedAdmin', 'System\Api\ResellerController@lockedAdmin');

        //configuration
        Route::get('config-login/record', 'System\Api\ConfigurationController@record');
        Route::post('config-login', 'System\Api\ConfigurationController@store');
        Route::post('config-login/upload', 'System\Api\ConfigurationController@uploadImage');




    });

}
