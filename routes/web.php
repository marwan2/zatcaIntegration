<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::group(['prefix'=>'/', 'middleware'=>['auth']], function(){
	Route::get('/', 'InvoicesController@index');
	Route::get('simple-invoice', 'InvoicesController@simpleInvoice');
	Route::get('simple-invoice2', 'InvoicesController@simpleInvoice2');
	Route::get('standard-invoice', 'InvoicesController@standardInvoice');
	Route::get('encode-file', 'InvoicesController@encodeFile');

	Route::get('invoices/validate', 'BusinessesController@validateXML');
	Route::get('invoices/{trans_no}/{business_id}/pdf', 'BusinessesController@getPDF');
	Route::get('invoices/{business_id}/show/{trans_no}/', 'BusinessesController@showInvoice');
	Route::get('invoices/{business_id}/{trans_no}', 'BusinessesController@getTemplate');
	Route::get('invoices/{business_id}/xml/{trans_no}', 'BusinessesController@generateXML');
	Route::get('invoices/{business_id}/reporting/{trans_no}', 'BusinessesController@reporting');
	Route::get('invoices', 'BusinessesController@invoices');

	Route::resource('businesses', 'BusinessesController');
});

Route::get('/home', 'HomeController@index')->name('home');
