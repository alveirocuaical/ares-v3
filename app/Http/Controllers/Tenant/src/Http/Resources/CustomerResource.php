<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CustomerResource extends JsonResource{

    public function toArray($request)
    {
       // Log::info("Transforming customer data: " . print_r($this, true));
        return [
            'customer_id' => $this['customer_id'] ?? 0,
            'customer_email' => $this['ResponseDian']['GetAcquirerResponse']['GetAcquirerResult']['ReceiverEmail'],
            'customer_full_name' => $this['ResponseDian']['GetAcquirerResponse']['GetAcquirerResult']['ReceiverName'],
        ];
    }
}
