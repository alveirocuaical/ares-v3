<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\repositories\Contracts\ItemRepositoryInterface;



class ItemService
{
    protected $itemRepository;

    public function __construct(ItemRepositoryInterface $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function getLatestSoldItems()
    {
        return $this->itemRepository->getLatestSoldItems();
    }

    public function findItemById($id)
    {
        return $this->itemRepository->findById($id);
    }

    public function findLastCodeItem()
    {
        return $this->itemRepository->findLastCode();
    }

    public function searchItemsByTerm($term, $request)
    {

        $posCollection = $this->itemRepository->searchItems($term);
        $itemsArray = $posCollection->toArray($request);
        $csvItems = $this->generateCSVFormat($itemsArray);
        return $csvItems;
    }

    public function getAllItems()
    {
        $posCollection = $this->itemRepository->all();
        $itemsArray = $posCollection->toArray(request());
        $csvItems = $this->generateCSVFormat($itemsArray);
        return $csvItems;
    }

    public function generateCSVFormat( $items){
        $columns = [
            'id',
            'item_id',
            'full_description',
            'description',
            'name',
            'currency_type_id',
            'category_id',
            'internal_id',
            'currency_type_symbol',
            'sale_unit_price',
            'purchase_unit_price',
            'unit_type_id',
            'calculate_quantity',
            'is_set',
            'tax_id',
            'aux_quantity',
            'aux_sale_unit_price',
            'edit_sale_unit_price',
            'sale_unit_price_with_tax',
            'stock',
            'warehouse_description',
            'lot_code',
            'date_of_due',
            'price1',
            'price2',
            'price3',
        ];

        $callback = function() use ($items, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($items as $item) {
                $stock = 0;
                $warehouse_description = '';
                if (isset($item['warehouses']) && count($item['warehouses']) > 0) {
                    $stock = $item['warehouses'][0]['stock'];
                    $warehouse_description = $item['warehouses'][0]['warehouse_description'];
                }

                $row = [
                    $item['id'],
                    $item['item_id'],
                    $item['full_description'],
                    $item['description'],
                    $item['name'],
                    $item['currency_type_id'],
                    $item['category_id'],
                    $item['internal_id'],
                    $item['currency_type_symbol'],
                    $item['sale_unit_price'],
                    $item['purchase_unit_price'],
                    $item['unit_type_id'],
                    $item['calculate_quantity'] ? 'true' : 'false',
                    $item['is_set'] ? 'true' : 'false',
                    $item['tax_id'],
                    $item['aux_quantity'],
                    $item['aux_sale_unit_price'],
                    $item['edit_sale_unit_price'],
                    $item['sale_unit_price_with_tax'],
                    $stock,
                    $warehouse_description,
                    $item['lot_code'],
                    $item['date_of_due'],
                    (float) ($item['item_unit_types'][0]['price1'] ?? '0'),
                    (float) ($item['item_unit_types'][0]['price2'] ?? '0'),
                    (float) ($item['item_unit_types'][0]['price3'] ?? '0'),
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return $callback;
    }


}
