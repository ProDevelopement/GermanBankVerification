<?php
namespace ProDevelopement\GermanBankVerification;

use Illuminate\Support\ServiceProvider;


class GBVServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    public function register(){
        
    }
}