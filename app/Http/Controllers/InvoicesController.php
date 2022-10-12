<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SimpleInvoice;

class InvoicesController extends Controller
{
	public function __construct() {
    }

    public function index() {
    	return view('welcome');
    }

	public function simpleInvoice(Request $req) {
        // Create PHP Native DomDocument object, that can be used to validate the generate XML
        $invoice = new \App\SimpleInvoice;
        $invoice->setSchema('2.1');

        $file = $invoice->fileName();
        $outputXML = $invoice->getSimpleInvoice();

        $dom = new \DOMDocument;
        $dom->loadXML($outputXML);
        $dom->save(public_path(SimpleInvoice::$base_dir) . '/'.$file);

        $fileAnchor = $invoice->fileAnchor($file);

        if($invoice->isValidSchema($dom)) {
        	return 'Valid document. ' . $fileAnchor;
        }
        return 'Document is not valid. ' . $fileAnchor;
	}

	public function simpleInvoice2(Request $req) {
        $invoice = new \App\SimpleInvoice;
        $invoice->setSchema('2.2');

        $file = $invoice->fileName();
        $outputXML = $invoice->getSimpleInvoice2();

        $dom = new \DOMDocument;
        $dom->loadXML($outputXML);
        $dom->save(public_path(SimpleInvoice::$base_dir) . '/'.$file);

        $fileAnchor = $invoice->fileAnchor($file);
        if($invoice->isValidSchema($dom)) {
        	return 'Valid document. ' . $fileAnchor;
        }
        return 'Document is not valid. ' . $fileAnchor;
	}

	public function encodeFile(Request $req) {
		$file = $req->get('filename');
		$file_encoded = '';
		if($file) {
			$file_encoded = base64_encode(file_get_contents(public_path(SimpleInvoice::$base_dir).'/'.$file));
		}
		return $file_encoded;
	}

	public function standardInvoice(Request $req) {

	}
}