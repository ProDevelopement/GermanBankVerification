<?php

Route::group(['namespace' => 'ProDevelopement\GermanBankVerification\Http\Controllers'], function () {
    Route::get('/gbv/updatedb', 'AutoPopulateController@index');
    Route::post('/gbv/updatedb', 'AutoPopulateController@autopopulate');
    Route::get('/gbv/test/{blz}/{kto}', 'AutoPopulateController@test');
    Route::get('/gbv/test/{blz}', 'AutoPopulateController@test');
    Route::get('/gbv/test', 'AutoPopulateController@test');
});
