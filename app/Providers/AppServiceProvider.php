<?php

namespace App\Providers;

use App\Models\Pharmacy\MedicineStockMovement;
use App\Models\Setting\GeneralSettings;
use App\Observers\MedicineStockMovementObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use URL;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        MedicineStockMovement::observe(MedicineStockMovementObserver::class);

        try {
            if (Schema::hasTable('general_settings')) {

                // Select only small columns, never logo_data / favicon_data
                $setting = GeneralSettings::select(
                    'id',
                    'system_name',
                    'phone',
                    'email',
                    'address',
                    'facebook',
                    'telegram',
                    'working_hours',
                    'logo_mime',
                    'favicon_mime',
                    'updated_at'
                )->first();

                View::share('setting', $setting);

                if ($setting) {
                    Config::set('adminlte.logo', '<b>' . e($setting->system_name) . '</b>');

                    Config::set(
                        'adminlte.logo_img',
                        $setting->logo_mime
                        ? url('settings/general/logo') . '?v=' . optional($setting->updated_at)->timestamp
                        : 'vendor/adminlte/dist/img/logo.jpg'
                    );
                }

            } else {
                View::share('setting', null);
            }
        } catch (\Throwable $e) {
            View::share('setting', null);
        }
    }
}
