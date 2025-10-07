<?php

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tenant\Item;
use App\Models\Tenant\Company;
use App\Models\Tenant\ItemUnitType;
use Illuminate\Routing\Controller;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Modules\Item\Imports\ItemListPriceImport;
use Maatwebsite\Excel\Excel;
use Modules\Item\Exports\ItemExport;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;


class ItemController extends Controller
{

    public function generateBarcode($id)
    {
        $item = Item::findOrFail($id);
        $colour = [150,150,150];
        $generator = new BarcodeGeneratorPNG();
        $temp = tempnam(sys_get_temp_dir(), 'item_barcode');
        file_put_contents($temp, $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 5, 70, $colour));
        $headers = [
            'Content-Type' => 'image/png',
        ];
        return response()->download($temp, "{$item->internal_id}.png", $headers);
    }
    public function showBarcodeLabel(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $company = Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        // Parámetros de tamaño y campos
        $width = $request->input('width', 32); // ancho etiqueta en mm
        $height = $request->input('height', 25); // alto etiqueta en mm
        $pageWidth = $request->input('pageWidth', 100); // ancho de hoja en mm
        $columns = $request->input('columns', 3); // etiquetas por fila
        $gapX = $request->input('gapX', 2); // espacio horizontal entre etiquetas
        $repeat = $request->input('repeat', 1); // cuántas etiquetas imprimir

        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        // Calcular filas necesarias
        $pageHeight = $height + $gapX + 0.1;

        $html = view('tenant.item.barcode_labels_grid', [
            'width' => $width,
            'height' => $height,
            'gapX' => $gapX,
            'columns' => $columns,
            'repeat' => $repeat,
            'item' => $item,
            'companyName' => $companyName,
            'fields' => $fields,
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
        ])->render();

        $mpdf = new Mpdf([
            'format' => [(float)$pageWidth, (float)$pageHeight], // ancho hoja x alto calculado
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->name);
        $filename = $safeName.'_barcode.pdf';

        return response($mpdf->Output($filename, 'I'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function generateBarcodes(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $company = Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        // Recibe parámetros de tamaño y campos
        $width = $request->input('width', 32);
        $height = $request->input('height', 25);
        $pageWidth = $request->input('pageWidth', 100);
        $columns = $request->input('columns', 3);
        $gapX = $request->input('gapX', 2);
        $repeat = $request->input('repeat', 1);

        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        // Obtener los items seleccionados
        $items = Item::whereIn('id', $ids)->get();

        // Generar un array de items repetidos según $repeat
        $allItems = [];
        foreach ($items as $item) {
            for ($i = 0; $i < $repeat; $i++) {
                $allItems[] = $item;
            }
        }

        // Calcular filas necesarias
        $total = count($allItems);
        $col = $columns;
        $rows = ceil($total / $col);
        $pageHeight = $height + $gapX + 0.1;

        $html = view('tenant.item.barcode_labels_grid', [
            'width' => $width,
            'height' => $height,
            'gapX' => $gapX,
            'columns' => $columns,
            'repeat' => $total,
            'items' => $allItems,
            'companyName' => $companyName,
            'fields' => $fields,
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
        ])->render();

        $mpdf = new Mpdf([
            'format' => [(float)$pageWidth, (float)$pageHeight],
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'etiquetas_barcode.pdf';

        return response($mpdf->Output($filename, 'I'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }


    public function coExport()
    {
        $records = Item::get();

        return (new ItemExport)
                ->records($records)
                ->download('Productos'.Carbon::now().'.xlsx');

    }


    public function importItemPriceLists(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new ItemListPriceImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

}
