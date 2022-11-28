<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::group(['prefix'=>'/', 'middleware'=>['auth']], function(){
	Route::get('xml/tests', 'TestingController@testXML');
	Route::post('xml/tests', 'TestingController@postTestXML')->name('testing.xml');

	Route::get('encode-file', 'InvoicesController@encodeFile');
	Route::get('invoices/validate', 'InvoicesController@validateXML');
	Route::get('invoices/{business_id}/reporting/{trans_no}', 'InvoicesController@reporting')->name('invoice.reporting');
	Route::get('invoices/{business_id}/compliance/{trans_no}', 'InvoicesController@checkInvoiceCompliance')->name('invoice.compliance');
	Route::get('invoices/{business_id}/clearance/{trans_no}', 'InvoicesController@reporting')->name('invoice.clearance');

	Route::get('invoices/{trans_no}/{business_id}/pdf', 'InvoicesController@getPDF');
	Route::get('invoices/{business_id}/show/{trans_no}/', 'InvoicesController@showInvoice');
	Route::get('invoices/{business_id}/{trans_no}', 'InvoicesController@getTemplate');
	Route::get('invoices/{business_id}/xml/{trans_no}', 'InvoicesController@generateXML');
	Route::get('invoices', 'InvoicesController@invoices');

	Route::get('businesses/{business_id}/pcsid/renewal', 'BusinessesController@certificateRenewal')->name('csid.renewal');
	Route::get('businesses/{business_id}/onboarding', 'BusinessesController@onBoarding')->name('onboarding');
	Route::get('businesses/{business_id}/geneate-certificate-pem', 'BusinessesController@generateCertPem')->name('cert.pem');
	Route::get('cert-download/{file}', 'HomeController@download');

	Route::get('account', 'HomeController@showUser');
	Route::get('account/refresh-token', 'HomeController@refreshToken');
	Route::resource('businesses', 'BusinessesController');
});

Route::get('/', 'HomeController@index');
Route::get('/home', 'HomeController@index')->name('home');
