<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\BankAccount;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;

class ReportBankReconciliationController extends Controller
{
    public function index()
    {
        return view('accounting::reports.bank_reconciliation');
    }

    public function records(Request $request)
    {
        $bankAccounts = BankAccount::where('status', 1)
            ->with('bank', 'currency', 'chart_of_account')
            ->get();

        return response()->json($bankAccounts);
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
}