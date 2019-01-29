<?php namespace App\Tool\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class BackServiceProvider extends ServiceProvider {
    
    
    public function register()
    {
        $defer = false;
        $this->app->bind('App\Tool\Modules\Service\Coupon\CouponServiceInterface', 'App\Tool\Modules\Service\Coupon\CouponService');
    }
}