<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Item;
use Illuminate\Http\Request;
use Modules\Item\Models\Category;
use App\Http\Resources\Tenant\ItemCollection;
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

        $warehouse_id = auth()->user()->establishment->id;

        $items = Item::where('apply_restaurant', 1)
            ->whereNotNull('internal_id')
            ->whereHas('warehouses', function ($query) use ($warehouse_id) {
                $query->where('warehouse_id', $warehouse_id);
            })
            ->get();

        $records = new ItemCollection($items);

        return [
            'success' => true,
            'data' => $records
        ];
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
