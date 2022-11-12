<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::group(['prefix'=>'/', 'middleware'=>['auth']], function(){

	Route::get('encode-file', 'InvoicesController@encodeFile');
	Route::get('invoices/validate', 'InvoicesController@validateXML');
	Route::get('invoices/{trans_no}/{business_id}/pdf', 'InvoicesController@getPDF');
	Route::get('invoices/{business_id}/show/{trans_no}/', 'InvoicesController@showInvoice');
	Route::get('invoices/{business_id}/{trans_no}', 'InvoicesController@getTemplate');
	Route::get('invoices/{business_id}/xml/{trans_no}', 'InvoicesController@generateXML');
	Route::get('invoices/{business_id}/reporting/{trans_no}', 'InvoicesController@reporting');
	Route::get('invoices', 'InvoicesController@invoices');
	Route::resource('businesses', 'BusinessesController');
});

Route::get('/', 'HomeController@index');
Route::get('/home', 'HomeController@index')->name('home');
