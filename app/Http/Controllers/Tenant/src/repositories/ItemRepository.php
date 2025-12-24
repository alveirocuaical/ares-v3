<?php
namespace App\Http\Controllers\Tenant\src\repositories;

use App\Http\Controllers\Tenant\src\repositories\Contracts\ItemRepositoryInterface;
use App\Models\Tenant\Configuration;
use Modules\Inventory\Models\ItemWarehouse;
use App\Models\Tenant\Item;
use App\Http\Resources\Tenant\PosCollection;

class ItemRepository implements ItemRepositoryInterface{

    public function getLatestSoldItems()  {
        $configuration =  Configuration::first();
        $latestItemsSold = ItemWarehouse::latest()->take(20)->pluck('item_id');
        $items = Item::whereIn('id', $latestItemsSold)
                    //->whereWarehouse()
                    ->whereIsActive()
                    ->take(10)
                    ->paginate(10);
        return new PosCollection($items, $configuration);
    }

    public function findById($id) {
        return Item::findOrFail($id);
    }

    public function findLastCode() {
        $numericCodes = Item::whereRaw('internal_id REGEXP "^[0-9]+$"')
                            ->pluck('internal_id')
                            ->map(function($code) {
                                return (int)$code;
                            });
        if($numericCodes->isEmpty()) {
            return '0000001';
        }
        $maxCode = $numericCodes->max();
        $nextCode = str_pad($maxCode + 1, 10, '0', STR_PAD_LEFT);
        return $nextCode;
    }


    public function searchItems($term, $size = 50) {

        $configuration =  Configuration::first();
        $items = Item::where('name', 'like',  '%' . $term . '%')
            ->orWhere('description', 'like',  '%' . $term . '%')
            ->orWhere('internal_id', 'like',  '%' . $term . '%')
            ->orWhereHas('category', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            })
            ->orWhereHas('brand', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            })
            ->whereWarehouse()
            ->whereIsActive()

            ->paginate($size);

        return new PosCollection($items, $configuration);
    }

    public function all() {
        $items = Item::whereWarehouse()
                    ->whereIsActive()
                    ->paginate(1000);

        return new PosCollection($items, Configuration::first());
    }
}
