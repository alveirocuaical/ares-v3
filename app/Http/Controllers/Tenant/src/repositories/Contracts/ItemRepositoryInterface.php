<?php
namespace App\Http\Controllers\Tenant\src\repositories\Contracts;

interface ItemRepositoryInterface
{
    public function getLatestSoldItems();
    public function findById($id);
    public function findLastCode();
    public function searchItems($term, $size = 50);
    public function all();
}
