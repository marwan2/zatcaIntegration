<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;

class TestingController extends Controller
{
    public $actions = [
        'invoice_pure'=>'Generate Pure XML without Extenstion, certificate, QR',
        'invoice_hash'=>'Generate Invoice Hash',
        'certificate_info'=>'Get Signing Certificate Info.',
        'digital_signature'=>'Generate Invoice Digital Signature',
        'invoice_qr'=>'Generate Invoice QR',
        'sign_invoice'=>'Sign Invoice',
    ];

    public function __construct() {
        $businesses = Business::orderBy('id', 'ASC')->get();

        view()->share('actions', $this->actions);
        view()->share('businesses', $businesses);
    }

    public function testXML(Request $req) {
        return view('testing.xml_actions');
    }

    public function postTestXML(Request $req) {
        $filename = $req->get('filename');
        $action = $req->get('action');
        $business_id = $req->get('business_id');

        if(!file_exists(public_path('xmls/'.$filename))) {
            throw new \Exception("File {$filename} not found");
        }

        $path = public_path('xmls/'.$filename);
        $file_content = file_get_contents($path);
        $sn = new \App\Signing;

        $business = Business::findOrFail($business_id);
        $sn->setBusiness($business);

        if($action == 'invoice_hash') {
            $xml = $sn->getInvoiceHash($file_content);
            return $xml;
        }

        if($action == 'invoice_pure') {
            $xml = $sn->getPureInvoiceString($file_content);
            return $xml;
        }

        if($action == 'certificate_info') {
            $production = false;
            $certificate = $sn->getCertificateString($production);
            $content = $sn->getCertificateInfo($certificate);
            return $content;
        }

        if($action == 'digital_signature') {
            $invoice_hash = $sn->getInvoiceHash($file_content);
            $signature = $sn->createInvoiceDigitalSignature($invoice_hash);
            return $signature;
        }

        if($action == 'sign_invoice') {
            $signed_invoice = $sn->signInvoice($file_content, false);
            if(is_array($signed_invoice)) {
                return $signed_invoice['0'];
            }
            return $signed_invoice;
        }

        return ' ************ END ************';
    }
}
