<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\BankAccount;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\BankReconciliationDetail;

class BankReconciliationController extends Controller
{
    public function index()
    {
        return view('accounting::bank_reconciliation.index');
    }

    public function columns()
    {
        return [
            'created_at' => 'Fecha de Creación',
            'month' => 'Mes de Conciliación',
            'bank_account' => 'Cuenta Bancaria',
        ];
    }

    public function bankAccounts()
    {
        // Devuelve todas las cuentas bancarias activas con su banco relacionado
        $bankAccounts = BankAccount::where('status', 1)
            ->with('bank')
            ->get();

        return response()->json($bankAccounts);
    }

    public function records(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = BankReconciliation::with('bankAccount');

        $this->Filters($request, $query);

        // Puedes agregar filtros aquí si lo necesitas

        $reconciliations = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "data" => $reconciliations->items(),
            "links" => [
                "first" => $reconciliations->url(1),
                "last" => $reconciliations->url($reconciliations->lastPage()),
                "prev" => $reconciliations->previousPageUrl(),
                "next" => $reconciliations->nextPageUrl(),
            ],
            "meta" => [
                "current_page" => $reconciliations->currentPage(),
                "from" => $reconciliations->firstItem(),
                "last_page" => $reconciliations->lastPage(),
                "path" => request()->url(),
                "per_page" => (string) $reconciliations->perPage(),
                "to" => $reconciliations->lastItem(),
                "total" => $reconciliations->total(),
            ]
        ]);
    }

    public function Filters(Request $request, $query)
    {
        // Filtro por fecha de creación (rango)
        if ($request->filled('column') && $request->input('column') == 'daterange' && $request->filled('value')) {
            $dates = explode('_', $request->input('value'));
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0], $dates[1]]);
            }
        }

        // Filtro por mes
        if ($request->filled('column') && $request->input('column') == 'month' && $request->filled('value')) {
            $query->where('month', $request->input('value'));
        }

        // Filtro por cuenta bancaria
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->input('bank_account_id'));
        }

    }

    public function movements(Request $request)
    {
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);

        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        // Buscar la cuenta bancaria y su cuenta contable asociada
        $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
        if (!$bank_account) {
            return response()->json([
                'movements' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => 1,
                ]
            ]);
        }

        // Query base
        $query = JournalEntryDetail::with(['journalEntry', 'chartOfAccount', 'thirdParty'])
            ->where('chart_of_account_id', $bank_account->chart_of_account_id)
            ->where('bank_account_id', $bank_account->id)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            });
        
        $total_debit = (clone $query)->sum('debit');
        $total_credit = (clone $query)->sum('credit');

        $total = $query->count();
        $details = $query->orderBy('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        $movements = [];
        foreach ($details as $detail) {
            $entry = $detail->journalEntry;
            $movements[] = [
                'id' => $detail->id, // ID único para selección
                'date' => $entry->date ?? '',
                'document' => $entry && $entry->journal_prefix ? ($entry->journal_prefix->prefix ?? '') . '-' . ($entry->number ?? '') : '',
                'payment_method' => $detail->payment_method_name ?? 'TRANSFERENCIA',
                'description' => $entry->description ?? '',
                'debit' => number_format($detail->debit ?? 0, 2, ',', '.'),
                'credit' => number_format($detail->credit ?? 0, 2, ',', '.'),
                'third_party_document' => $detail->thirdParty ? $detail->thirdParty->document : '',
                'third_party_name' => $detail->thirdParty ? $detail->thirdParty->name : '',
                'cheque_number' => $detail->cheque_number ?? '',
            ];
        }

        return response()->json([
            'movements' => $movements,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
            'totals' => [
                'debit' => $total_debit,
                'credit' => $total_credit,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');

        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
        $auxiliar = $bank_account && $bank_account->chart_of_account ? $bank_account->chart_of_account->code : null;
        $chart_account = $auxiliar ? ChartOfAccount::where('code', $auxiliar)->first() : null;

        // Saldo inicial igual que libro de bancos
        $saldo_inicial = 0;
        if ($chart_account) {
            $si_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id) // <-- AQUÍ FILTRAS POR BANCO
                ->whereHas('journalEntry', function($q) {
                    $q->where('journal_prefix_id', 11)
                    ->where('status', 'posted');
                })
                ->get();
            $saldo_inicial = $si_query->sum('debit') - $si_query->sum('credit');

            $saldo_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id) // <-- AQUÍ FILTRAS POR BANCO
                ->whereHas('journalEntry', function($q) use ($start_date) {
                    $q->where('date', '<', $start_date)
                    ->where('status', 'posted')
                    ->where('journal_prefix_id', '!=', 11);
                })
                ->get();
            $saldo_inicial += $saldo_query->sum('debit') - $saldo_query->sum('credit');
        }

        // Solo traslados a favor (DB): ingresos asociados al banco en el mes
        $traslado_a_favor = 0;
        if ($chart_account) {
            $traslado_a_favor_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                    $q->whereBetween('date', [$start_date, $end_date])
                    ->where('status', 'posted')
                    ->where('journal_prefix_id', '!=', 11);
                })
                ->get();
            $traslado_a_favor = $traslado_a_favor_query->sum('debit');
        }

        // Traslados en contra (CR): egresos asociados al banco en el mes
        $traslados_en_contra = 0;
        if ($chart_account) {
            $traslados_en_contra_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                    $q->whereBetween('date', [$start_date, $end_date])
                    ->where('status', 'posted');
                })
                ->get();
            $traslados_en_contra = $traslados_en_contra_query->sum('credit');
        }

        // Obtener los detalles del mes para calcular el saldo final
        $details_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
            ->where('bank_account_id', $bank_account->id)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted')
                ->where('journal_prefix_id', '!=', 11);
            })
            ->get();

        // Calcular saldo final del libro de bancos (saldo siguiente según libros)
        $saldo_siguiente_libros = $saldo_inicial;
        foreach ($details_query as $detail) {
            $saldo_siguiente_libros += $detail->debit - $detail->credit;
        }

        // Otros campos
        $notas_creditos = 0;
        $cheques_girados = 0;
        $consignaciones = 0;
        $notas_debitos = 0;
        $cuatro_x_mil = 0;
        $total_ingresos = $saldo_inicial + $traslado_a_favor + $notas_creditos + $consignaciones;
        $total_egresos = $cheques_girados + $notas_debitos + $traslados_en_contra + $cuatro_x_mil;
        $saldo_siguiente_extracto = 0;
        $diferencia = 0;

        $company = Company::active();
        $filters = [
            'month' => $month,
            'bank_account' => $bank_account,
            'auxiliar' => $auxiliar,
        ];

        $report_data = compact(
            'company',
            'filters',
            'saldo_inicial',
            'traslado_a_favor',
            'traslados_en_contra',
            'notas_creditos',
            'cheques_girados',
            'notas_debitos',
            'cuatro_x_mil',
            'total_ingresos',
            'total_egresos',
            'saldo_siguiente_libros',
            'saldo_siguiente_extracto',
            'diferencia'
        );

        $pdf = PDF::loadView('accounting::reports.bank_reconciliation_pdf', $report_data)->setPaper('a4', 'landscape');
        $filename = 'Conciliacion_Bancaria_' . date('YmdHis');
        return $pdf->stream($filename . '.pdf');
    }

    public function storeDraft(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|integer',
            'month' => 'required|string',
            'date' => 'required|date',
            'saldo_libro' => 'required',
            'saldo_extracto' => 'required',
            'detalles_mas' => 'array',
            'detalles_menos' => 'array',
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $data['bank_account_id'],
            'month' => $data['month'],
            'date' => $data['date'],
            'saldo_libro' => $data['saldo_libro'],
            'saldo_extracto' => $data['saldo_extracto'],
            'status' => 'draft', // Asegúrate de tener este campo en la tabla
        ]);

        // Guardar detalles "Más"
        foreach ($data['detalles_mas'] ?? [] as $detalle) {
            BankReconciliationDetail::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'tipo' => 'mas',
                'fecha' => $detalle['date'] ?? null,
                'nombre_tercero' => $detalle['nombre_tercero'] ?? null,
                'origen' => $detalle['origen'] ?? null,
                'n_soporte' => $detalle['n_soporte'] ?? null,
                'cheque' => $detalle['cheque'] ?? null,
                'concepto' => $detalle['concepto'] ?? null,
                'valor' => $detalle['credit'] ?? 0,
            ]);
        }
        // Guardar detalles "Menos"
        foreach ($data['detalles_menos'] ?? [] as $detalle) {
            BankReconciliationDetail::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'tipo' => 'menos',
                'fecha' => $detalle['date'] ?? null,
                'nombre_tercero' => $detalle['nombre_tercero'] ?? null,
                'origen' => $detalle['origen'] ?? null,
                'n_soporte' => $detalle['n_soporte'] ?? null,
                'cheque' => $detalle['cheque'] ?? null,
                'concepto' => $detalle['concepto'] ?? null,
                'valor' => $detalle['debit'] ?? 0,
            ]);
        }

        return response()->json(['success' => true, 'id' => $reconciliation->id]);
    }
}