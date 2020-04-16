<?php

namespace Armincms\Localization;

use Laravel\Nova\Nova; 
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class LocalizationServiceProvider extends ServiceProvider 
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if(! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/localization.php', 'localization');
        }

        Nova::serving([$this, 'servingNova']); 
    }

    /**
     * Nova serving event.
     * 
     * @return 
     */
    public function servingNova()
    { 
        Nova::script('nova-localization', __DIR__.'/../dist/js/field.js');   
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    { 
        $this->app->instance('armincms.localization', new Repository($this->app['config']));

        $this->registerPublishes();
    }

    /**
     * Register laravel publishign files.
     * 
     * @return void
     */
    public function registerPublishes()
    { 
        $this->publishes([
            __DIR__.'/../config/localization.php' => config_path('localization.php')
        ], 'localization-config');
    } 
}
