<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Item;
use Illuminate\Http\Request;
use Modules\Item\Models\Category;
use App\Http\Resources\Tenant\ItemCollection;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Inventory\Models\Warehouse as ModuleWarehouse;
use Exception;


class RestaurantController extends Controller
{
    public function index()
    {
        return view('tenant.restaurant.index');
    }
    /**
     * Listado para DataTable
     * GET /restaurant-items/records
     */
    public function records(Request $request)
    {
        $query = Item::query()
            ->whereNotIsSet()
            ->whereIsActive()
            ->with(['category', 'unit_type'])
            ->orderBy('id', 'DESC');

        // Búsqueda global
        if ($request->search && $request->search['value']) {
            $value = $request->search['value'];
            $query->where(function ($q) use ($value) {
                $q->where('name', 'like', "%$value%")
                ->orWhere('internal_id', 'like', "%$value%");
            });
        }

        // Filtro por columna exacta seleccionada en el DataTable
        if ($request->column && $request->value) {
            $query->where($request->column, 'like', "%{$request->value}%");
        }

        $records = $query->paginate(20);

        $items = collect($records->items())->map(function($item) {
            $itemArray = $item->toArray();
            $itemArray['stock'] = $item->getStockByWarehouse();
            return $itemArray;
        });

        return [
            'data' => $items,
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
            ],
        ];
    }

    /**
     * Definición de columnas para DataTable
     * GET /restaurant-items/columns
     */
    public function columns()
    {
        return [
            'name' => 'Nombre',
            'internal_id' => 'Código',
            'category_id' => 'Categoría',
            'sale_unit_price' => 'Precio',
        ];
    }

    /**
     * GET /restaurant-items/categories
     */
    public function categories2()
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    /**
     * POST /restaurant-items/toggle-visible
     */
    public function toggleVisible(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tenant.items,id',
            'visible' => 'required|boolean',
        ]);

        $item = Item::find($request->id);
        $item->apply_restaurant = $request->visible;
        $item->save();

        return [
            'success' => true,
            'message' => 'Actualizado correctamente.'
        ];
    }

    /**
     * POST /restaurant-items/store
     */
    public function store(Request $request)
    {
        $item = Item::create($this->itemFields($request));

        return [
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $item,
        ];
    }

    /**
     * PUT /restaurant-items/update/{id}
     */
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $item->update($this->itemFields($request));

        return [
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $item,
        ];
    }

    /**
     * DELETE /restaurant-items/delete/{id}
     */
    public function destroy($id)
    {
        try {

            $item = Item::findOrFail($id);
            $this->deleteRecordInitialKardex($item);
            $item->delete();

            return [
                'success' => true,
                'message' => 'Producto eliminado con éxito'
            ];

        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false,'message' => 'El producto esta siendo usado por otros registros, no puede eliminar'] : ['success' => false,'message' => 'Error inesperado, no se pudo eliminar el producto'];

        }


    }

    /**
     * Normaliza los campos recibidos
     */
    private function itemFields(Request $request)
    {
        return [
            'name' => $request->name,
            'internal_id' => $request->internal_id,
            'description' => $request->description,
            'sale_unit_price' => $request->sale_unit_price,
            'unit_type_id' => $request->unit_type_id,
            'category_id' => $request->category_id,
            'restaurant_visible' => $request->restaurant_visible ?? true,
            'stock' => $request->stock ?? 0,
            'calculate_quantity' => $request->calculate_quantity ?? false,
        ];
    }

    private function deleteRecordInitialKardex($item){

        if($item->kardex->count() == 1){
            ($item->kardex[0]->type == null) ? $item->kardex[0]->delete() : false;
        }

    }

    //API

    public function items(Request $request){

        $establishment_id = auth()->user()->establishment_id;
        $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();

        // Ítems tipo producto (no AIU, no set, activos, sin series, con warehouse)
        $items_u = Item::whereNotItemsAiu()
            ->whereWarehouse()
            ->whereIsActive()
            ->whereNotIsSet()
            ->where('apply_restaurant', 1)
            ->where('series_enabled', false)
            ->orderBy('internal_id')
            ->get();

        // Ítems tipo servicio (unit_type_id ZZ, no AIU, activos, sin series)
        $items_s = Item::whereNotItemsAiu()
            ->where('unit_type_id', 'ZZ')
            ->whereIsActive()
            ->where('apply_restaurant', 1)
            ->where('series_enabled', false)
            ->orderBy('internal_id')
            ->get();

        $items = $items_u->merge($items_s);

        $documentController = app(\Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController::class);
        $document_items = $this->getItemsTableData();

        $records = new ItemCollection($items);

        return [
            'success' => true,
            'data' => $records,
            'realProducts' => $document_items,
        ];
    }

    protected function getItemsTableData()
    {
        $establishment_id = auth()->user()->establishment_id;
        $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();

        $items_u = Item::whereNotItemsAiu()
            ->whereWarehouse()
            ->whereIsActive()
            ->whereNotIsSet()
            ->where('apply_restaurant', 1)
            ->where('series_enabled', false)
            ->orderBy('internal_id')
            ->get();

        $items_s = Item::whereNotItemsAiu()
            ->where('unit_type_id', 'ZZ')
            ->whereIsActive()
            ->where('apply_restaurant', 1)
            ->where('series_enabled', false)
            ->orderBy('internal_id')
            ->get();

        $items = $items_u->merge($items_s);

        $documentController = app(\Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController::class);

        return collect($items)->transform(function ($row) use ($warehouse, $documentController) {

            $detail = $documentController->getFullDescription($row, $warehouse);
            $sale_unit_price_with_tax = $this->getSaleUnitPriceWithTax($row);

            return [
                'id' => $row->id,
                "item_id" => $row->id,
                'name' => $row->name,
                'full_description' => $detail['full_description'],
                'brand' => $detail['brand'],
                'category' => $detail['category'],
                'stock' => $detail['stock'],
                'internal_id' => $row->internal_id,
                'description' => $row->description,
                'price' => $row->sale_unit_price,
                'currency_type_id' => $row->currency_type_id,
                'currency_type_symbol' => $row->currency_type->symbol,
                'tax_id' => $row->tax_id,
                'is_set' => (bool) $row->is_set,
                'edit_unit_price' => false,
                'aux_quantity' => 1,
                'edit_sale_unit_price' => strval(round($sale_unit_price_with_tax, 2)),
                'aux_sale_unit_price' => number_format($row->sale_unit_price, 2, ".",""),
                'category_id' => $row->category_id,
                'sets' => collect($row->sets)->transform(function($r){
                    return [
                        $r->individual_item->name
                    ];
                }),
                'is_favorite' => (bool) $row->is_favorite,
                'sale_unit_price_with_tax' => $sale_unit_price_with_tax,
                'unit_price' => $row->sale_unit_price,
                'sale_unit_price' => round($sale_unit_price_with_tax, 2),
                'purchase_unit_price' => $row->purchase_unit_price,
                'unit_type_id' => $row->unit_type_id,
                'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                'calculate_quantity' => (bool) $row->calculate_quantity,
                'has_igv' => (bool) $row->has_igv,
                'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                'image' => $row->image != "imagen-no-disponible.jpg"
                    ? url("/storage/uploads/items/" . $row->image)
                    : url("/logo/" . $row->image),
                'image_url' => ($row->image !== 'imagen-no-disponible.jpg') 
                            ? asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image) 
                            : asset("/logo/{$row->image}"),

                'item_unit_types' => collect($row->item_unit_types)->transform(function ($row) {
                    return [
                        'id' => $row->id,
                        'description' => $row->description,
                        'item_id' => $row->item_id,
                        'unit_type_id' => $row->unit_type_id,
                        'unit_type' => $row->unit_type,
                        'quantity_unit' => $row->quantity_unit,
                        'price1' => $row->price1,
                        'price2' => $row->price2,
                        'price3' => $row->price3,
                        'price_default' => $row->price_default,
                    ];
                }),

                'warehouses' => collect($row->warehouses)->transform(function ($row) use ($warehouse) {
                    return [
                        'warehouse_description' => $row->warehouse->description,
                        'stock' => $row->stock,
                        'warehouse_id' => $row->warehouse_id,
                        'checked' => $row->warehouse_id == $warehouse->id,
                    ];
                }),

                'attributes' => $row->attributes ?? [],

                'lots_group' => collect($row->lots_group)->transform(function ($row) {
                    return [
                        'id' => $row->id,
                        'code' => $row->code,
                        'quantity' => $row->quantity,
                        'date_of_due' => $row->date_of_due,
                        'checked' => false,
                    ];
                }),

                'lots' => $row->item_lots
                    ->where('has_sale', false)
                    ->where('warehouse_id', $warehouse->id)
                    ->transform(function ($row) {
                        return [
                            'id' => $row->id,
                            'series' => $row->series,
                            'date' => $row->date,
                            'item_id' => $row->item_id,
                            'warehouse_id' => $row->warehouse_id,
                            'has_sale' => (bool) $row->has_sale,
                            'lot_code' => $row->item_loteable_type && isset($row->item_loteable->lot_code)
                                ? $row->item_loteable->lot_code
                                : null,
                        ];
                    }),

                'lots_enabled' => (bool) $row->lots_enabled,
                'series_enabled' => (bool) $row->series_enabled,
                'unit_type' => $row->unit_type,
                'tax' => $row->tax,
                'active' => (bool) $row->active,
            ];
        });
    }

    private function getSaleUnitPriceWithTax($item)
    {
        $advanced_config = AdvancedConfiguration::first();
        $is_tax_included = $advanced_config->item_tax_included;
        if($is_tax_included) {
            return number_format($item->sale_unit_price * ( 1 + ($item->tax->rate ?? 0) / ($item->tax->conversion ?? 1)), 2, ".","");
        }
        return $item->sale_unit_price;
    }

    public function savePrice(Request $request) {
        $item = Item::find($request->id);
        $item->sale_unit_price = $request->sale_unit_price;
        $item->save();

        return [
            'success' => true,
            'message' => 'Precio editado correctamente.'
        ];
    }

    public function categories(Request $request){
        $records = Category::all();
        return [
            'success' => true,
            'data' => $records
        ];
    }
    
}
