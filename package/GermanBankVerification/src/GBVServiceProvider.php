<?php
namespace ProDevelopement\GermanBankVerification;

use Illuminate\Support\ServiceProvider;


class GBVServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/database');
        $this->loadViewsFrom(__DIR__.'/views', 'GBV');
    }

    public function register(){
        
    }
}