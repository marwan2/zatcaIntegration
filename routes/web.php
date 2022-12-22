<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::group(['prefix'=>'/', 'middleware'=>['auth']], function(){
	Route::get('xml/tests', 'TestingController@testXML');
	Route::post('xml/tests', 'TestingController@postTestXML')->name('testing.xml');
	Route::get('encode-file', 'InvoicesController@encodeFile');

	Route::get('invoices/validate', 'InvoicesController@validateXML');
	Route::get('invoices/reporting/{trans_no}', 'InvoicesController@reporting')->name('invoice.reporting');
	Route::get('invoices/compliance/{trans_no}', 'InvoicesController@checkInvoiceCompliance')->name('invoice.compliance');
	Route::get('invoices/clearance/{trans_no}', 'InvoicesController@reporting')->name('invoice.clearance');
	Route::get('invoices/{trans_no}/pdf', 'InvoicesController@getPDF');
	Route::get('invoices/show/{trans_no}', 'InvoicesController@showInvoice');
	Route::get('invoices/{trans_no}', 'InvoicesController@getTemplate');
	Route::get('invoices/xml/{trans_no}', 'InvoicesController@generateXML');
	Route::get('invoices', 'InvoicesController@invoices');
	Route::resource('logs', 'LogsController');

	Route::get('credit-notes/show/{trans_no}', 'CreditNotesController@showInvoice');
	Route::get('credit-notes/xml/{trans_no}', 'CreditNotesController@generateXML');
	Route::get('credit-notes/compliance/{trans_no}', 'CreditNotesController@checkInvoiceCompliance')->name('cn.compliance');
	Route::get('credit-notes', 'CreditNotesController@index');

	Route::get('businesses/select', 'BusinessesController@selectBusiness')->name('business.select');
	Route::get('businesses/switch/{business_id}', 'BusinessesController@switchBusiness')->name('business.switch.do');
	Route::get('businesses/{business_id}/pcsid/renewal', 'BusinessesController@certificateRenewal')->name('csid.renewal');
	Route::get('businesses/{business_id}/onboarding', 'BusinessesController@onBoarding')->name('onboarding');
	Route::get('businesses/{business_id}/geneate-certificate-pem', 'BusinessesController@generateCertPem')->name('cert.pem');
	Route::post('businesses/{business_id}/update-erp-onboarding-status', 'BusinessesController@updateErpOnboarding')->name('onb.erp');
	Route::post('businesses/{business_id}/erp-db-updates', 'BusinessesController@updateErpDB')->name('onb.dberp');
	Route::get('businesses/session-reload', 'BusinessesController@sessionReload')->name('bs.sess.reload');
	Route::get('cert-download/{file}', 'HomeController@download');

	Route::get('account', 'HomeController@showUser');
	Route::get('account/refresh-token', 'HomeController@refreshToken');
	Route::get('account/password', 'HomeController@getPassword');
	Route::post('account/password', 'AccountController@postPassword');
	Route::resource('businesses', 'BusinessesController');
});

Route::get('/', 'HomeController@index');
Route::get('/home', 'HomeController@index')->name('home');
