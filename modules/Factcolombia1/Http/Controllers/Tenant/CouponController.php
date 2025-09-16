<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Maatwebsite\Excel\Facades\Excel;
use Modules\Factcolombia1\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Factcolombia1\Models\Tenant\{
    ConfigurationPurchaseCoupon,
};

use Modules\Factcolombia1\Http\Resources\Tenant\CouponCollection;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('factcolombia1::coupon.index');
    }

    public function records(Request $request)
    {
    $records = ConfigurationPurchaseCoupon::query()->orderBy('id', 'desc');

        return new CouponCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function record($id)
    {
        $record = ConfigurationPurchaseCoupon::findOrFail($id);

        return $record;
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'minimum_purchase_amount' => 'required|numeric|min:0',
            'establishment' => 'required|string|max:255',
            'coupon_date' => 'required|date',
            'status' => 'required|boolean',
        ]);

        if ($data['status']) {
            ConfigurationPurchaseCoupon::where('status', true)->update(['status' => false]);
        }

        $coupon = ConfigurationPurchaseCoupon::create($data);

        return [
            'success' => true,
            'message' => "Se registro con éxito el cupón."
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, $id) {

        $coupon = ConfigurationPurchaseCoupon::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'minimum_purchase_amount' => 'required|numeric|min:0',
            'establishment' => 'required|string|max:255',
            'coupon_date' => 'required|date',
            'status' => 'required|boolean',
        ]);

        // Si se quiere activar este cupón, desactivamos los demás
        if ($data['status']) {
            ConfigurationPurchaseCoupon::where('status', true)->where('id', '!=', $coupon->id)->update(['status' => false]);
        }

        $coupon->update($data);

        return [
            'success' => true,
            'message' => "Se actualizo con éxito el cupón."
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy($id) {

        $coupon = ConfigurationPurchaseCoupon::findOrFail($id);
        $coupon->delete();

        return [
            'success' => true,
            'message' => "Se eliminó con éxito el cupón {$coupon->title}."
        ];

    }

}
