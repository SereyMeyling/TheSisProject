<?php

namespace App\Providers;

use App\Models\Setting\GeneralSettings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        if (Schema::hasTable('general_settings')) {

            $setting = GeneralSettings::first();

            View::share('setting', $setting);

            if ($setting) {

                Config::set(
                    'adminlte.logo',
                    '<b>' . $setting->system_name . '</b>'
                );

                Config::set(
                    'adminlte.logo_img',
                    $setting->logo
                    ? 'storage/' . $setting->logo
                    : 'vendor/adminlte/dist/img/logo.jpg'
                );

            }

        } else {

            // prevent undefined variable in views
            View::share('setting', null);

        }
    }
}
