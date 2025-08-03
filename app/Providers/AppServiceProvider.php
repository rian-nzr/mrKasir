<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\OrderProduct;
use Dedoc\Scramble\Scramble;
use Filament\Support\Assets\Js;
use App\Observers\ReportObserver;
use App\Observers\OrderObserver;
use App\Observers\TransactionObserver;
use App\Observers\OrderProductObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        Report::observe(ReportObserver::class);
        Order::observe(OrderObserver::class);
        Transaction::observe(TransactionObserver::class);
        OrderProduct::observe(OrderProductObserver::class);

        FilamentAsset::register([
            Js::make('printer-thermal', asset('js/printer-thermal.js')),
            Js::make('currency-formatter', asset('js/currency-formatter.js'))
        ]);
    }
}
