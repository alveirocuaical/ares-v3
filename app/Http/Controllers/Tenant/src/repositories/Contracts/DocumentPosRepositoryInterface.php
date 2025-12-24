<?php
namespace App\Http\Controllers\Tenant\src\repositories\Contracts;

use App\Models\Tenant\DocumentPos;

interface DocumentPosRepositoryInterface
{

    public function getDocumentPos(int $id): DocumentPos;

    public function addPayment(object $payment);

    public function getDocumentPosBasicDetails(int $id);

}
