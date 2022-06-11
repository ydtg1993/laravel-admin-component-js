<?php

namespace DLP\Component;

use Illuminate\Support\ServiceProvider;

class ComponentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'component');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/laravel-admin-component-js/')
        ]);
    }
}