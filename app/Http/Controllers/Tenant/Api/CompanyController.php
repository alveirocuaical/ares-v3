<?php
namespace App\Http\Controllers\Tenant\Api;

use App\CoreFacturalo\Facturalo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\User;
use App\Models\Tenant\Establishment;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Factcolombia1\Models\TenantService\Company as ServiceCompany;
use App\Models\Tenant\Person;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use App\Models\Tenant\ConfigurationPos;
use Modules\Factcolombia1\Models\Tenant\Tax as Taxsistem;
use Modules\Factcolombia1\Models\TenantService\Tax as ServiceTax;
use Modules\Factcolombia1\Models\Tenant\TypeUnit;

class CompanyController extends Controller
{
   
    use FinanceTrait;

    public function record(Request $request) {

        $user = new User();
        if(\Auth::user()){
            $user = \Auth::user();
        }
        

        $establishment_id =  $user->establishment_id;
        $establishments = Establishment::without(['country', 'department', 'city'])->where('id', $establishment_id)->get();
        // $series = collect($user->getSeries())->values()->all();
        // $series = null;

        $resolutions_invoice = TypeDocument::where('code', TypeDocument::INVOICE_CODE)
            ->whereNotNull('resolution_number')
            ->whereNotNull('prefix')
            ->where('show_in_establishments', '!=', 'none')
            ->where(function ($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function ($q) use ($establishment_id) {
                        $q->where('show_in_establishments', 'custom')
                            ->whereJsonContains('establishment_ids', $establishment_id);
                    });
            })
            ->get([
                'id',                   // ID de la resolución
                'name',                 // Nombre del tipo de documento
                'code',                 // Código del tipo de documento
                'prefix',               // Prefijo de la resolución
                'resolution_number',    // Número de resolución
                'resolution_date',      // Fecha de resolución
                'resolution_date_end',  // Fecha fin de resolución
                'technical_key',        // Clave técnica
                'from',                 // Rango inicial
                'to',                   // Rango final
                'generated'             // Cantidad generada
            ]);
        // Resoluciones de POS
        $resolutions_pos = ConfigurationPos::whereNotNull('resolution_number')
            ->whereNotNull('prefix')
            ->where('show_in_establishments', '!=', 'none')
            ->where(function ($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function ($q) use ($establishment_id) {
                        $q->where('show_in_establishments', 'custom')
                            ->whereJsonContains('establishment_ids', $establishment_id);
                    });
            })
            ->get([
                'id',                   // ID de la resolución POS
                'prefix',               // Prefijo de la resolución
                'resolution_number',    // Número de resolución
                'resolution_date',      // Fecha de resolución
                'date_from',            // Fecha inicio de vigencia
                'date_end',             // Fecha fin de vigencia
                'from',                 // Rango inicial
                'to',                   // Rango final
                'generated',            // Cantidad generada
                'cash_type',            // Tipo de caja
                'show_in_establishments', // Mostrar en establecimientos
                'establishment_ids',     // IDs de establecimientos asociados
                'electronic'             // Indicador de resolución electrónica
            ]);
            
        $customers = Person::without(['country', 'department', 'city'])
                               ->whereType('customers')
                               ->whereIsEnabled()
                               ->orderBy('name')
                               ->get()->transform(function ($row) {
                                    return [
                                        'id'                                     => $row->id,
                                        'dv'                                     => $row->dv,
                                        'registro_mercantil'                     => "000000",
                                        'tipo_organizacion'                      => $row->type_person_id,
                                        'municipality_id_fact'                   => $row->city_id,
                                        'type_regime_id'                         => $row->type_regime_id,
                                        'type_liability_id'                      => $row->type_obligation_id,
                                        'codigo_tipo_documento_identidad'        => $row->identity_document_type_id,
                                        'numero_documento'                       => $row->number,
                                        'apellidos_y_nombres_o_razon_social'     => $row->name,
                                        'codigo_pais'                            => $row->country_id,
                                        'direccion'                              => $row->address,
                                        'correo_electronico'                     => $row->email,
                                        'telefono'                               => $row->telephone,
                                    ];

                                });
        $payment_method_types = PaymentMethod::all();

        $payment_destinations = $this->getPaymentDestinations();

        // Obtener tasas del sistema y del API
        $tasas = $this->getTasasForApi()->getData(true);

        // Obtener unidades de medida
        $type_units = $this->getTypeUnitsForApi()->getData(true);
        
        return [
            'series' => $series?? null,
            'resolutionsInvoice' => $resolutions_invoice,
            'resolutionsPos' => $resolutions_pos,
            'establishments' => $establishments,
            'company' =>  Company::active(),
            'customers' => $customers,
            'payment_method_types' => $payment_method_types,
            'payment_destinations' => $payment_destinations,
            'tasas_sistema' => $tasas['tasas_sistema'],
            'tasas_api' => $tasas['tasas_api'],
            'type_units' => $type_units['type_units'],
        ];

    }
    public function getTasasForApi()
    {
        $tasas_sistema = Taxsistem::all()->map(function($tax) {
            return [
                'id' => $tax->id,
                'code' => $tax->code,
                'name' => $tax->name,
                'rate' => $tax->rate,
                'total' => 0,
                'in_tax' => $tax->in_tax,
                'in_base' => $tax->in_base,
                'type_tax' => $tax->type_tax,
                'retention' => 0,
                'conversion' => $tax->conversion,
                'type_tax_id' => $tax->type_tax_id,
                'is_retention' => $tax->is_retention,
                'is_percentage' => $tax->is_percentage,
                'is_fixed_value' => $tax->is_fixed_value,
            ];
        });

        $tasas_api = ServiceTax::all()->map(function($tax) {
            return [
                'id' => $tax->id,
                'name' => $tax->name,
                'code' => $tax->code,
                'description' => $tax->description,
                // otros atributos del catálogo API
            ];
        });

        return response()->json([
            'tasas_sistema' => $tasas_sistema,
            'tasas_api' => $tasas_api,
        ]);
    }

    public function getTypeUnitsForApi()
    {
        $units = TypeUnit::all()->map(function($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'deleted_at' => $unit->deleted_at,
                'created_at' => $unit->created_at,
                'updated_at' => $unit->updated_at,
            ];
        });

        return response()->json([
            'type_units' => $units,
        ]);
    }
}
