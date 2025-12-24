<?php
namespace App\Http\Controllers\Tenant\src\repositories;



use Modules\Factcolombia1\Models\Tenant\Tax;
use App\Http\Controllers\Tenant\src\repositories\Contracts\TaxesRepositoryInterface;
use Modules\Factcolombia1\Models\TenantService\{
    Tax as TypeTax
};


class TaxesRepositoryImpl implements TaxesRepositoryInterface
{
    private $model;
    private $taxeTypesModel;

    public function __construct()
    {
        $this->model = new Tax();
        $this->taxeTypesModel = new TypeTax();

    }
    public function getAll()
    {

        $taxes = $this->model::all()->transform(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'rate' =>  $row->rate,
                'conversion' =>  $row->conversion,
                'is_percentage' =>  $row->is_percentage,
                'is_fixed_value' =>  $row->is_fixed_value,
                'is_retention' =>  $row->is_retention,
                'in_base' =>  $row->in_base,
                'in_tax' =>  $row->in_tax,
                'deleted_at' =>  $row->deleted_at,
                'created_at' =>  $row->created_at,
                'updated_at' =>  $row->updated_at,
                'type_tax_id' =>  $row->type_tax_id,
                'chart_account_sale' =>  $row->chart_account_sale,
                'chart_account_purchase' =>  $row->chart_account_purchase,
                'chart_account_return_sale' =>  $row->chart_account_return_sale,
                'chart_account_return_purchase' =>  $row->chart_account_return_purchase,
                'type_tax' => $row->type_tax,
            ];
        });

        return $taxes;
    }

    public function getAllTaxeTypes()
    {
        return $this->taxeTypesModel::all();
    }
}
