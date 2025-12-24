<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources;

use App\Models\Tenant\Configuration;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        $configuration = Configuration::first();
        $res = $this->resource;

        $get = function($path, $default = null) use ($res) {
            return data_get($res, $path, $default);
        };

        // Helper para formatear números de forma segura
        $fmt = function($value) use ($configuration) {
            $v = is_null($value) ? 0 : $value;
            return number_format((float) $v, $configuration ? $configuration->decimal_quantity : 2, ".", "");
        };

        // Warehouses: usar el elemento de la colección / array correctamente
        $warehouses = collect($get('warehouses', []))->map(function ($warehouse) use ($get) {
            return [
                'warehouse_description' => data_get($warehouse->warehouse, 'description', null),
                'stock' => data_get($warehouse, 'stock', null),
            ];
        })->values()->toArray();

        // item_unit_types: si viene como colección/array, devolver en forma segura
        $item_unit_types = collect($get('item_unit_types', []))->map(function ($item_unit_type) {
            return data_get($item_unit_type, 'description', $item_unit_type);
        })->values()->toArray();

        // Sale unit price with tax: intentar calcular a partir de datos disponibles
        $sale_unit_price = $get('sale_unit_price', $get('unit_price', 0));
        $sale_unit_price_with_tax = $get('sale_unit_price_with_tax', $sale_unit_price);

        return [
            'id' => null,
            'item_id' => $get('id'),
            'item' => [
                'id' => $get('id'),
                'item_id' => $get('id'),
                'full_description' => $get('internal_id') ? ($get('internal_id') . ' - ' . $get('description')) : $get('name'),
                'name' => $get('name'),
                'description' => $get('description'),
                'currency_type_id' => data_get($get('currency_type'), 'id', $get('currency_type_id')),
                'internal_id' => $get('internal_id'),
                'currency_type_symbol' => data_get($get('currency_type'), 'symbol'),
                'sale_unit_price' => $fmt($get('request_price')),
                'unit_type_id' => $get('unit_type_id'),
                'calculate_quantity' => (bool) $get('calculate_quantity', false),
                'tax_id' => $get('tax_id'),
                'is_set' => (bool) $get('is_set', false),
                'edit_unit_price' => false,
                'aux_quantity' => 1,
                'edit_sale_unit_price' => $fmt($get('request_price_with_tax')),
                'aux_sale_unit_price' => $fmt($get('request_price_with_tax')),
                'image_url' => $this->buildImageUrl($get('image')),
                'warehouses' => $warehouses,
                'category_id' => $get('category.id', $get('category_id')),
                'sets' => collect($get('sets', []))->map(function ($r) {
                    return [ data_get($r, 'individual_item.name', $r) ];
                })->values()->toArray(),
                'unit_type' => $get('unit_type')->toArray($request),
                'tax' => $get('tax')->toArray($request),
                'item_unit_types' => $item_unit_types,
                'sale_unit_price_with_tax' => $fmt($get('request_price_with_tax')),
                'unit_price' => $get('request_price'),
            ],
            'code' => null,
            'discount' => 0,
            'name' => null,
            'unit_price_value' => $get('request_price'),
            'unit_price' => $get('request_price'),
            'quantity' => $get('request_quantity'),
            'aux_quantity' => $get('request_quantity'),
            'subtotal' => $fmt($get('request_subtotal')),
            'tax' => [
                'id'             => $get('tax.id'),
                'name'           => $get('tax.name'),
                'code'           => $get('tax.code'),
                'rate'           => $fmt($get('tax.rate', 0)),
                'conversion'     => $fmt($get('tax.conversion', 0)),
                'is_percentage'  => (bool) $get('tax.is_percentage', false),
                'is_fixed_value' => (bool) $get('tax.is_fixed_value', false),
                'is_retention'   => (bool) $get('tax.is_retention', false),
                'in_base'        => (bool) $get('tax.in_base', false),
                'in_tax'         => $get('tax.in_tax'),
                'type_tax_id'    => $get('tax.type_tax_id'),
                'type_tax' => [
                    'id'          => $get('tax.type_tax.id'),
                    'name'        => $get('tax.type_tax.name'),
                    'description' => $get('tax.type_tax.description'),
                    'code'        => $get('tax.type_tax.code'),
                ],
                'retention'      => $get('request_tax_retention'),
                'total'          => $fmt($get('request_tax_total')),
            ],
            'tax_id' => $get('tax_id'),
            'total' => $get('request_total'),
            'total_tax' => $get('request_total_tax'),
            'edited_price' => false,
            'type_unit' => (object)[],
            'unit_type_id' => $get('unit_type_id'),
            'item_unit_types' => [],
            'IdLoteSelected' => null,
            'sale_unit_price_with_tax' => $get('request_price_with_tax'),
            'refund' => false,
            'sale_unit_price' => $get('request_price'),
            'presentation' => null,
            'unit_type' => $get('unit_type')->toArray($request),
        ];
    }

    protected function buildImageUrl($image)
    {
        if (! $image) {
            return null;
        }
        if ($image !== 'imagen-no-disponible.jpg') {
            return asset('storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'items' . DIRECTORY_SEPARATOR . $image);
        }
        return asset("/logo/{$image}");
    }
}
