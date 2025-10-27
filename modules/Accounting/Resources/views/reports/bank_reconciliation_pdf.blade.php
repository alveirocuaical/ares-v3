<!DOCTYPE html>
@php
    use Modules\Factcolombia1\Models\Tenant\Company as CoCompany;
    use App\Models\Tenant\BankAccount;

    $company = $company ?? CoCompany::active();
    $bank_account = $filters['bank_account'] ?? null;
    if (is_numeric($bank_account)) {
        $bank_account = BankAccount::find($bank_account);
    }
    $periodo = $filters['month'] ?? '';
    $logo_path = $company && $company->logo ? public_path("storage/uploads/logos/{$company->logo}") : null;
@endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conciliación Bancaria</title>
    <style>
        @page { margin: 15px; }
        html { font-family: Arial, sans-serif; font-size: 11px; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: top; border: none; }
        .company-title { font-size: 20px; font-weight: bold; color: #222; }
        .company-info { font-size: 12px; color: #444; line-height: 1.5; }
        .bank-info { font-size: 12px; color: #222; font-weight: bold; }
        .logo-box img { max-width: 120px; }
        .report-title { font-size: 18px; font-weight: bold; color: #0066cc; text-align: right; }
        .yellow { background: #ffe600; font-weight: bold; }
        .subtitle { font-size: 13px; font-weight: bold; color: #333; background: #e6f2ff; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: center; }
        th { background: #e6f2ff; color: #222; }
        .notes { font-size: 9px; margin-top: 10px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .amount { font-family: "Courier New", monospace; height: 12px; }
        .logo-cell { width: 18%; }
        .company-cell { width: 52%; }
        .report-cell { width: 30%; }
        .highlight { background: #bde3ff; }
        .check { height: 20px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logo_path && file_exists($logo_path))
                    <div class="logo-box">
                        <img src="data:{{mime_content_type($logo_path)}};base64,{{base64_encode(file_get_contents($logo_path))}}" alt="Logo">
                    </div>
                @endif
            </td>
            <td class="company-cell">
                <div class="company-title">{{ $company->name ?? '' }}</div>
                <div class="company-info">
                    <div><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
                    <div><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
                    <div><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '' }}</div>
                    <div><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
                </div>
            </td>
            <td class="report-cell">
                <div class="report-title yellow">CONCILIACION BANCARIAS</div>
                <div class="bank-info">
                    <div><strong>Banco:</strong> {{ $bank_account->description ?? 'Caja General' }}</div>
                    <div><strong>N° Cuenta:</strong> {{ $bank_account->number ?? '-' }}</div>
                    <div><strong>Periodo:</strong>
                        @php
                            setlocale(LC_TIME, 'spanish');
                            $fecha = strftime('%B del %Y', strtotime($periodo.'-01'));
                        @endphp
                        {{ strtoupper($fecha) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="yellow">SALDO ANTERIOR</td>
            <td class="amount">{!! isset($saldo_inicial) && $saldo_inicial !== 0 ? $saldo_inicial : '&nbsp;' !!}</td>
            <td class="yellow">CHEQUES GIRADOS</td>
            <td class="amount">{!! isset($cheques_girados) && $cheques_girados !== 0 ? $cheques_girados : '&nbsp;' !!}</td>
        </tr>
        <tr>
            <td class="yellow">CONSIGNACIONES</td>
            <td class="amount">{{ isset($consignaciones) && $consignaciones !== 0 ? $consignaciones : '' }}</td>
            <td class="yellow">NOTAS DEBITOS</td>
            <td class="amount">{{ isset($notas_debitos) && $notas_debitos !== 0 ? $notas_debitos : '' }}</td>
        </tr>
        <tr>
            <td class="yellow">NOTAS CREDITOS</td>
            <td class="amount">{{ isset($notas_creditos) && $notas_creditos !== 0 ? $notas_creditos : '' }}</td>
            <td class="yellow">TRASLADOS EN CONTRA (CR)</td>
            <td class="amount">{{ isset($traslados_en_contra) && $traslados_en_contra !== 0 ? $traslados_en_contra : 0 }}</td>
        </tr>
        <tr>
            <td class="yellow">TRASLADO A FAVOR (DB)</td>
            <td class="amount">{{ isset($traslado_a_favor) && $traslado_a_favor !== 0 ? $traslado_a_favor : 0 }}</td>
            <td class="yellow">4X1000</td>
            <td class="amount">{{ isset($cuatro_x_mil) && $cuatro_x_mil !== 0 ? $cuatro_x_mil : '' }}</td>
        </tr>
        <tr>
            <td class="yellow">TOTAL INGRESOS</td>
            <td class="amount">{{ isset($total_ingresos) && $total_ingresos !== 0 ? $total_ingresos : '' }}</td>
            <td class="yellow">TOTAL EGRESOS</td>
            <td class="amount">{{ isset($total_egresos) && $total_egresos !== 0 ? $total_egresos : '' }}</td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <th class="yellow">CHEQUES GIRADOS Y NO COBRADOS</th>
            <th class="yellow">SALDO SIGUIENTE SEGÚN LIBROS</th>
            <th class="yellow">SALDO SIGUIENTE SEGÚN EXTRACTO BANCARIO</th>
            <th class="yellow">DIFERENCIA</th>
        </tr>
        <tr>
            <td class="amount">{{ isset($cheques_girados) && $cheques_girados !== 0 ? $cheques_girados : '' }}</td>
            <td class="amount">{{ isset($saldo_siguiente_libros) && $saldo_siguiente_libros !== 0 ? $saldo_siguiente_libros : '' }}</td>
            <td class="amount">{{ isset($saldo_siguiente_extracto) && $saldo_siguiente_extracto !== 0 ? $saldo_siguiente_extracto : '' }}</td>
            <td class="amount">{{ isset($diferencia) && $diferencia !== 0 ? $diferencia : '' }}</td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <th class="yellow">NO DE CHEQUE</th>
            <th class="yellow">FECHA</th>
            <th class="yellow">BENEFICIARIO</th>
            <th class="yellow">VALOR CHEQUE</th>
        </tr>
        @for($i = 0; $i < 5; $i++)
            <tr>
                <td class="check"></td>
                <td class="check"></td>
                <td class="check"></td>
                <td class="check"></td>
            </tr>
        @endfor
    </table>

    <br>

</body>
</html>