<?php

namespace App\Providers;

use App\Models\Tenant\Document;
use App\Observers\DocumentObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot() {
        if (config('tenant.force_https')) URL::forceScheme('https');
        Document::observe(DocumentObserver::class);
    }

    public function register() {
        $bindings = config('repositories');
        foreach ($bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        if ($this->app->environment('local', 'testing')) {
            $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
        }

    }
}
