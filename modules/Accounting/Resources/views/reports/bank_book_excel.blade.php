<!-- filepath: c:\laragon\www\facturadorpro2\modules\Accounting\Resources\views\reports\bank_book_Pdf.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Bancos</title>
    <style>
        @page { margin: 10px; }
        html { font-family: sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        th, td { border: 1px solid #333; padding: 3px; text-align: center; }
        .header-main {
            font-size: 16px;
            font-weight: bold;
            background: #00ff62;
            color: #222;
            text-align: left;
            padding: 8px 0;
        }
        .header-sub {
            font-size: 12px;
            font-weight: bold;
            background: #cde9cb;
            color: #333;
            text-align: left;
            padding: 4px 0;
        }
        .header-table td {
            border: none;
            padding: 2px 8px;
        }
        .subtitle {
            background: #00ff62;
        }
        .notes { font-size: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <table class="header-table" style="margin-bottom: 10px;">
        <tr>
            <td colspan="4" class="header-main">
                {{ $company->name ?? '' }}
            </td>
            <td colspan="4" class="header-main" style="text-align:right;">
                LIBRO DE BANCOS
            </td>
        </tr>
        <tr>
            <td colspan="8" class="header-sub">
                <strong>NIT:</strong> {{ $company->number ?? '' }}<br>
            </td>
        </tr>
        <tr>
            <td colspan="8" class="header-sub">
                <strong>PERIODO:</strong> {{ $filters['month'] ?? '' }} &nbsp; 
                <strong>CTA:</strong> {{ $filters['bank_account']->description ?? '' }} &nbsp; 
                <strong>AUXILIAR:</strong> {{ $filters['auxiliar'] ?? '' }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>FECHA</th>
                <th>DOCUMENTO</th>
                <th>TRANSFERENCIA</th>
                <th>CONCEPTO</th>
                <th>TIPO</th>
                <th>DÉBITO</th>
                <th>CRÉDITO</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align:right;">SALDO INICIAL</td>
                <td>{{ number_format($saldo_inicial, 2, ',', '.') }}</td>
            </tr>
            @php $saldo = $saldo_inicial; @endphp
            @foreach($details as $detail)
                @php
                    $debit = $detail->debit ?? 0;
                    $credit = $detail->credit ?? 0;
                    $saldo += $debit - $credit;
                    $entry = $detail->journalEntry;
                    $account = $detail->chartOfAccount;
                @endphp
                <tr>
                    <td>{{ $entry->date ?? '' }}</td>
                    <td>
                        {{ $entry->journal_prefix->prefix ?? '' }}-{{ $entry->number ?? '' }}
                    </td>
                    <td>
                        @php
                            $payment_name = '';
                            // Detectar si es asiento de devolución
                            $is_refund = false;
                            if($entry->description && (stripos($entry->description, 'devolución') !== false || stripos($entry->description, 'nota de crédito') !== false)) {
                                $is_refund = true;
                            }
                            if($entry->document && $entry->document->payments && $entry->document->payments->count()) {
                                $payment = $entry->document->payments->first();
                                if($payment) $payment_name = $payment->payment_method_name;
                            }
                            elseif($entry->purchase && $entry->purchase->payments && $entry->purchase->payments->count()) {
                                $payment = $entry->purchase->payments->first();
                                if($payment) $payment_name = $payment->payment_method_name;
                            }
                            elseif($entry->document_pos && $entry->document_pos->payments && $entry->document_pos->payments->count()) {
                                $payment = $entry->document_pos->payments->first();
                                if($payment) $payment_name = $payment->payment_method_name;
                            }
                            elseif($entry->support_document && $entry->support_document->payments && $entry->support_document->payments->count()) {
                                $payment = $entry->support_document->payments->first();
                                if($payment) $payment_name = $payment->payment_method_name;
                            }
                        @endphp
                        {{ $is_refund ? 'DEVOLUCIÓN' : ($payment_name ?: 'TRANSFERENCIA') }}
                    </td>
                    <td>{{ $entry->description ?? '' }}</td>
                    <td>
                        @if($debit > 0)
                            CI
                        @elseif($credit > 0)
                            CE
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ number_format($debit, 2, ',', '.') }}</td>
                    <td>{{ number_format($credit, 2, ',', '.') }}</td>
                    <td>{{ number_format($saldo, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="7" style="text-align:right;">SALDO FINAL</td>
                <td>{{ number_format($saldo_final, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>