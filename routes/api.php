<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix'=>'/', 'namespace'=>'Api', 'middleware'=>['auth:api']], function() {
    Route::get('test', 'ApiController@test');
    Route::post('onboarding', 'ApiController@onBoarding');
    Route::post('reporting', 'ApiController@reporting');
    Route::post('compliance-check', 'ApiController@checkInvoiceCompliance');
    Route::post('erp-onboarding-update', 'ApiController@updateErpOnboarding');
});
