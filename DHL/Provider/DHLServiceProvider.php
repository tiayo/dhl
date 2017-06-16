<?php

namespace DHL\Provider;

use DHL\Api\LabelEvent;
use DHL\Api\TrackingEvent;
use Illuminate\Support\ServiceProvider;

class DHLServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../Api/dhl.php' => config_path('dhl.php'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('DHL', function ($app) {
            return new TrackingEvent();
        });

        $this->app->singleton('DHLLabel', function ($app) {
            return new LabelEvent();
        });
    }
}

