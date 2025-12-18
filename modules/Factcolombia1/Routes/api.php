<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/document-received/{cufe}/{state}', 'Api\Tenant\DocumentReceivedController@documentReceived')->name('document.received');

$currentHostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if ($currentHostname) {
    Route::domain($currentHostname->fqdn)->group(function() {
        Route::middleware('auth:api')->group(function() {
            Route::prefix('co-documents')->group(function() {
                Route::get('tables', 'Api\Tenant\DocumentController@tables');
                Route::get('table/items', 'Api\Tenant\DocumentController@table');
                Route::post('', 'Api\Tenant\DocumentController@store');
                Route::post('res', 'Api\Tenant\DocumentController@storeApi');
                Route::get('print-ticket/{id}', 'Api\Tenant\DocumentController@printTicket');
                Route::get('downloadFile/{filename}', 'Api\Tenant\DocumentController@downloadFile');
                Route::get('items-search', 'Api\Tenant\DocumentController@searchItems');
                Route::get('documents-search', 'Api\Tenant\DocumentController@searchDocuments');
                Route::get('customer-search', 'Api\Tenant\DocumentController@searchCustomers');
            });
            Route::prefix('co-documents-pos')->group(function() {
                Route::post('res', 'Api\Tenant\DocumentPosController@storeApi');
                Route::get('print-ticket/{id}', 'Api\Tenant\DocumentPosController@printTicket');
                //Route::get('downloadFile/{filename}', 'Api\Tenant\DocumentPosController@downloadFile');
                Route::get('voided/{id}', 'Api\Tenant\DocumentPosController@voided');
            });
        });
    });
}
