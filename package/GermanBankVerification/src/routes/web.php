<?php

Route::group(['namespace' => 'ProDevelopement\GermanBankVerification\Http\Controllers'], function () {
    Route::get('/gbv/updatedb', 'AutoPopulateController@index');
    Route::post('/gbv/updatedb', 'AutoPopulateController@autopopulate');
});
