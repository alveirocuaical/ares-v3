<?php
namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ThirdParty;
use Barryvdh\DomPDF\Facade as PDF;
use Maatwebsite\Excel\Facades\Excel;

class ReportThirdController extends Controller
{
    public function index()
    {
        return view('accounting::reports.third_report');
    }

    public function records(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $query = ThirdParty::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('document', 'like', "%$search%");
            });
        }

        $third_parties = $query->orderBy('name')->get();

        // Devuelve solo los datos necesarios para el select
        $thirds = $third_parties->map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->name . ' (' . $t->document . ')',
            ];
        });

        return response()->json([
            'data' => $thirds
        ]);
    }

    public function export(Request $request)
    {
        $format = $request->input('export', 'pdf');
        $type = $request->input('type');
        $third_id = $request->input('third_id');
        $month = $request->input('month'); // formato yyyy-MM

        // Obtener el tercero
        $third = null;
        if(strpos($third_id, 'person_') === 0) {
            $third = \App\Models\Tenant\Person::find(str_replace('person_', '', $third_id));
            $third_name = $third ? $third->name : '';
            $third_document = $third ? $third->number : '';
        } elseif(strpos($third_id, 'worker_') === 0) {
            $third = \Modules\Payroll\Models\Worker::find(str_replace('worker_', '', $third_id));
            $third_name = $third ? $third->full_name : '';
            $third_document = $third ? $third->identification_number : '';
        } elseif(strpos($third_id, 'seller_') === 0) {
            $third = \App\Models\Tenant\Seller::find(str_replace('seller_', '', $third_id));
            $third_name = $third ? $third->full_name : '';
            $third_document = $third ? $third->document_number : '';
        } else {
            $third = ThirdParty::find($third_id);
            $third_name = $third ? $third->name : '';
            $third_document = $third ? $third->document : '';
        }

        // Buscar detalles de asientos del mes y del tercero
        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        $details = \Modules\Accounting\Models\JournalEntryDetail::where('third_party_id', $third ? $third->id : null)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            })
            ->with(['journalEntry', 'chartOfAccount'])
            ->orderBy('id')
            ->get();

        // Agrupar por código y nombre de cuenta, separando débitos y créditos
        $grouped = [];
        foreach ($details as $d) {
            $key = $d->chartOfAccount->code . '|' . ($d->chartOfAccount->name ?? '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'codigo' => $d->chartOfAccount->code,
                    'cuenta' => $d->chartOfAccount->name ?? '',
                    'debito' => 0,
                    'credito' => 0,
                ];
            }
            $grouped[$key]['debito'] += $d->debit;
            $grouped[$key]['credito'] += $d->credit;
        }

        // Genera filas separadas si hay ambos valores
        $rows = [];
        foreach ($grouped as $g) {
            if ($g['debito'] > 0) {
                $rows[] = [
                    'codigo' => $g['codigo'],
                    'cuenta' => $g['cuenta'],
                    'debito' => $g['debito'],
                    'credito' => 0,
                ];
            }
            if ($g['credito'] > 0) {
                $rows[] = [
                    'codigo' => $g['codigo'],
                    'cuenta' => $g['cuenta'],
                    'debito' => 0,
                    'credito' => $g['credito'],
                ];
            }
        }

        $data = [
            'third_name' => $third_name,
            'third_document' => $third_document,
            'month' => $month,
            'rows' => $rows,
        ];

        $pdf = PDF::loadView('accounting::reports.third_report_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('ReporteTercero.pdf');
    }
    public function exportAll(Request $request)
    {
        $format = $request->input('export', 'pdf');

        // Obtiene todos los terceros (clientes, proveedores, empleados, vendedores y terceros manuales)
        $thirds = collect();

        // Clientes y proveedores
        $persons = \App\Models\Tenant\Person::whereIn('type', ['customers', 'suppliers'])->get();
        foreach ($persons as $p) {
            $thirds->push([
                'tipo' => ucfirst($p->type),
                'nombre' => $p->name,
                'documento' => $p->number,
                'direccion' => $p->address,
                'telefono' => $p->telephone,
                'email' => $p->email,
            ]);
        }

        // Empleados
        $workers = \Modules\Payroll\Models\Worker::all();
        foreach ($workers as $w) {
            $thirds->push([
                'tipo' => 'Empleado',
                'nombre' => $w->full_name,
                'documento' => $w->identification_number,
                'direccion' => $w->address,
                'telefono' => $w->cellphone,
                'email' => $w->email,
            ]);
        }

        // Vendedores
        $sellers = \App\Models\Tenant\Seller::all();
        foreach ($sellers as $s) {
            $thirds->push([
                'tipo' => 'Vendedor',
                'nombre' => $s->full_name,
                'documento' => $s->document_number,
                'direccion' => $s->address,
                'telefono' => $s->phone,
                'email' => $s->email,
            ]);
        }

        // Terceros manuales
        $manuals = ThirdParty::all();
        foreach ($manuals as $m) {
            $thirds->push([
                'tipo' => 'Tercero',
                'nombre' => $m->name,
                'documento' => $m->document,
                'direccion' => $m->address,
                'telefono' => $m->phone,
                'email' => $m->email,
            ]);
        }

        $data = [
            'thirds' => $thirds,
        ];

        $pdf = PDF::loadView('accounting::reports.third_report_all_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('ReporteTodosTerceros.pdf');
    }
}