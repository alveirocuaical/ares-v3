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
        .header { font-size: 12px; font-weight: bold; background: #ffe600; }
        .subtitle { background: #ffe600; }
        .notes { font-size: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="9" class="header">{{ $company->name ?? '' }}</td>
            <td colspan="2" class="header">LIBRO DE BANCOS</td>
        </tr>
        <tr>
            <td colspan="11" class="subtitle">
                NIT: {{ $company->number ?? '' }}<br>
                PERIODO: {{ $filters['month'] ?? '' }}<br>
                CTA: {{ $filters['bank_account']->description ?? '' }}<br>
                AUXILIAR: {{ $filters['auxiliar'] ?? '' }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>FECHA</th>
                <th>DOCUMENTO</th>
                <th>TRANSFERENCIA</th>
                <th>NOMBRE</th>
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
                    <td>{{ $entry->transfer_type ?? '' }}</td>
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

    <div class="notes">
        <strong>NOTAS O SIGNIFICADOS</strong><br>
        1. FECHA: Fecha de elaboración del documento<br>
        2. DOCUMENTO: Número de ingreso o egreso<br>
        3. TRANSFERENCIA: Si es cheque, transferencia, traslado<br>
        4. NOMBRE: Comprobante de ingreso o egreso<br>
        5. TIPO: CI (Ingreso), CE (Egreso)<br>
        6. DÉBITO: Ingresos por clientes u otros conceptos<br>
        7. CRÉDITO: Egresos a proveedores, servicios, etc.<br>
        8. SALDO: Saldo anterior + débitos - créditos
    </div>
</body>
</html>