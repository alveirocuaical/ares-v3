<?php
namespace App\Http\Controllers\Tenant\src\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\src\services\TaxesService;

class TaxController extends Controller
{
    protected $taxesService;

    public function __construct( TaxesService $taxesService)
    {
        $this->taxesService = $taxesService;
    }

    public function getAllTaxes()
    {
        return response()->streamDownload(
            $this->taxesService->getAllTaxes(),
            'taxes_'.date('Ymd').'.csv',
            [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-store, no-cache',
                'Pragma' => 'no-cache',
            ]
        );
    }

    public function getAllTaxeTypes()
    {
        return response()->streamDownload(
            $this->taxesService->getAllTaxeTypes(),
            'tax_types_'.date('Ymd').'.csv',
            [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-store, no-cache',
                'Pragma' => 'no-cache',
            ]
        );
    }
}
