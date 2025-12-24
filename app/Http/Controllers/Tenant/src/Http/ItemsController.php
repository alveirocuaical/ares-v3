<?php

namespace App\Http\Controllers\Tenant\src\Http;

use Mpdf\Mpdf;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Http\Controllers\Tenant\src\services\ItemService;

class ItemsController extends Controller
{
    protected $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    public function getLatestSoldItems()
    {
        return $this->itemService->getLatestSoldItems();
    }

    public function generateBarcode($id, Request $request)
    {


        $printPrice = (boolean)$request->input('printPrice') ?? false;
        $supplier = $request->input('supplier') ?? '';

        $request->validate([
            'width' => 'sometimes|numeric|min:3|max:100',
            'height' => 'sometimes|numeric|min:1|max:500'
        ]);

        $width = (float)$request->input('width', 210);
        $height = (float)$request->input('height', 297);

        $item = $this->itemService->findItemById($id);
        $colour = [0,0,0]; //? change this to get a different color bar code
        $generator = new BarcodeGeneratorPNG();
        $temp = tempnam(sys_get_temp_dir(), 'item_barcode');
        file_put_contents($temp, $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 5, 70, $colour));

        $headers = [
            'Content-Type' => 'application/pdf',
        ];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$width, $height],
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font' => 'dejaVuSans',

        ]);
        $html = view()
                ->file(base_path('app/CoreFacturalo/Templates/pdf/default/ares_pdf/item-print-pdf.blade.php'),
                [
                'item' => $item,
                'barcode' => base64_encode(file_get_contents($temp)),
                'printPrice' => $printPrice,
                'supplier' => $supplier
                ])->render();

        $mpdf->WriteHTML($html);
        $pdf_content = $mpdf->Output('', 'S');
        unlink($temp);
        return response($pdf_content, 200, $headers);

    }

    public function findLastCodeItem()
    {
        $lastCode = $this->itemService->findLastCodeItem();
        return response()->json(['lastCode' => $lastCode]);

    }

    /**
     * Search items
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function search_items(Request $request)
    {
        $term = $request->input('input_item', '');
        $posCollection = $this->itemService->searchItemsByTerm($term, $request);

        $callback = $posCollection;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="items.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get all items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllItems()
    {
        $items = $this->itemService->getAllItems();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="items.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        return response()->stream($items, 200, $headers);
    }

}
