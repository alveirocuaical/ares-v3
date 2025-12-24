<?php
namespace App\Http\Controllers\Tenant\src\Configs;

use App\Http\Controllers\Tenant\src\repositories\Contracts\CustomerRepositoryInterface;

use App\Http\Controllers\Tenant\src\repositories\ItemRepository;
use App\Http\Controllers\Tenant\src\repositories\IncomeRepositoryImpl;
use App\Http\Controllers\Tenant\src\repositories\RemissionPaymentImpl;
use App\Http\Controllers\Tenant\src\repositories\DocumentPosRepositoryImpl;
use App\Http\Controllers\Tenant\src\repositories\Contracts\ItemRepositoryInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\IncomeRepositoryInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\RemissionPaymentInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\DocumentPosRepositoryInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\PaymentMethodInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\TaxesRepositoryInterface;
use App\Http\Controllers\Tenant\src\repositories\CustomerRespositoryImpl;
use App\Http\Controllers\Tenant\src\repositories\PaymentMethodImpl;
use App\Http\Controllers\Tenant\src\repositories\TaxesRepositoryImpl;

return [
    ItemRepositoryInterface::class => ItemRepository::class,
    RemissionPaymentInterface::class => RemissionPaymentImpl::class,
    DocumentPosRepositoryInterface::class => DocumentPosRepositoryImpl::class,
    IncomeRepositoryInterface::class => IncomeRepositoryImpl::class,
    CustomerRepositoryInterface::class => CustomerRespositoryImpl::class,
    TaxesRepositoryInterface::class => TaxesRepositoryImpl::class,
    PaymentMethodInterface::class => PaymentMethodImpl::class,
];

