<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Business;
use App\Invoice;
use App\Helper;
use App\ReportingLog;
use Log;

class ApiController extends Controller
{
    public function test(Request $req) {
        $user = $req->user();
        $hl = new Helper;
        return $hl->code(200)->msg('It works.')->res(['user'=>$user]);
    }

    public function onBoarding(Request $req) {
        $hl = new Helper;
        $bs = new Business;
        $result = true;

        $validation = Validator::make($req->all(), Business::$api_validation);
        if ($validation->fails()) {
            $errors = $validation->errors();
            return $hl->code(400)->msg($errors->first())->res();
        }

        $data = $req->all();
        $data['ERP_xPrefix'] = $req->header('X-PREFIX');
        $data['ERP_AuthToken'] = $req->header('AUTH-TOKEN');

        if($bs->alreadyOnboarded($data)) {
            return $hl->code(301)->msg('Business already onboarded.')->res();
        }

        $business = $bs->createBusiness($data);

        if($business) {
            $updated = $business->updateERPDB();
        }

        if(!$business->csrExists()) {
            // Set CSR config file
            $csr_content = $business->getCsrContent();
            $privateKey = $business->getPrivateKey();
            $result = $business->generateCSR($csr_content, $privateKey);
        }

        if($result) {
            $api = new \App\API;
            $api->setBusiness($business);
            $ccsid = $api->issueComplianceCertificate();
            $pcsid = $api->issueProductionCertificate();
        }

        if($pcsid && $ccsid) {
            $business->updateOnboardingStatus(true);
            return $hl->code(200)->msg('Business onboarding process completed successfully.')->res();
        } else {
            $business->updateOnboardingStatus(false);
        }

        return $hl->code(301)->msg('Error while onboarding.')->res();
    }

    public function reporting(Request $req) {
        $hl = new Helper;
        $business = (new Business)->getBusiness($req->header('X-Prefix'));
        $trans_no = $req->get('trans_no');
        $trans_type = $req->get('trans_type');

        if($business && $trans_no) {
            $invoice = Invoice::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = Invoice::getInvoiceFromDb($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no);

            if($trans_type == \App\Helper::ERP_CREDITNOTE) {
                $inv->setTypeCode(Invoice::CREDIT_NOTE);
            }
            $invoice_xml = $inv->getXML();

            // Save XML file
            $invoice_path = public_path(\App\XMLInvoice::$base_dir) . '/' . $inv->fileName();
            $dom = new \DOMDocument;
            $dom->loadXML($invoice_xml);
            $dom->save($invoice_path);

            // Sign Invoice
            $signing = new \App\Signing;
            $signing->setBusiness($business);
            list($signed_invoice_string, $invoice_hash, $qr) = $signing->signInvoice($invoice_xml, false);

            // Encode Invoice
            $signed_invoice_encoded = base64_encode($signed_invoice_string);

            // Call reporting API
            $api = new \App\API;
            $api->setBusiness($business);
            $output = $api->reporting($signed_invoice_encoded, $invoice_hash);

            if (isset($output['validationResults']) && isset($output['validationResults']['status']) == 'PASS') {
                if(isset($output['clearanceStatus']) && $output['clearanceStatus'] == 'CLEARED') {
                    $msg = 'Invoice compliance passed successfully';
                }
            } else {
                $msg = "Invoice is not complaint with ZATCA";
                Log::warning($msg);
            }

            ReportingLog::addLog('Reporting', $business, $invoiceDB->id, $trans_no, $output, $trans_type);
            Log::info($output);
            return $hl->code(200)->msg($msg)->res($output);
        }

        $msg = 'Missing params on reporting invoice #' . $trans_no;
        Log::error($msg);
        return $hl->code(401)->msg($msg)->res($output);
    }

    /**
     * Check Invoice Compliance 
     */
    public function checkInvoiceCompliance(Request $req, $business_id=null, $trans_no=null) {
        $hl = new Helper;
        $business = (new Business)->getBusiness($req->header('X-PREFIX'));
        $trans_no = $req->get('trans_no');
        $trans_type = $req->get('trans_type');

        if($business && $trans_no) {
            $invoice = Invoice::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = Invoice::getInvoiceFromDb($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no);

            if($trans_type == \App\Helper::ERP_CREDITNOTE) {
                $inv->setTypeCode(Invoice::CREDIT_NOTE);
            }
            $invoice_xml = $inv->getXML();

            // Save XML file
            $invoice_path = public_path(\App\XMLInvoice::$base_dir) . '/' . $inv->fileName();
            $dom = new \DOMDocument;
            $dom->loadXML($invoice_xml);
            $dom->save($invoice_path);

            // Sign Invoice
            $signing = new \App\Signing;
            $signing->setBusiness($business);
            list($signed_invoice_string, $invoice_hash, $qr) = $signing->signInvoice($invoice_xml, false);

            // Encode Invoice
            $signed_invoice_encoded = base64_encode($signed_invoice_string);

            // Call Compliance API
            $api = new \App\API;
            $api->setBusiness($business);
            $api->setInvoice($invoiceDB);
            $output = $api->invoiceCompliance($signed_invoice_encoded, $invoice_hash);
            $msg = '';

            if (isset($output['validationResults']) && isset($output['validationResults']['status']) == 'PASS') {
                if(isset($output['clearanceStatus']) && $output['clearanceStatus'] == 'CLEARED') {
                    $msg = 'Invoice compliance passed successfully';
                }
            } else {
                $msg = "Invoice is not complaint with ZATCA";
                Log::warning($msg);
            }

            // Save action to log table
            ReportingLog::addLog('Compliance', $business, $invoiceDB->id, $trans_no, $output, $trans_type);
            Log::info($output);
            return $hl->code(200)->msg($msg)->res($output);
        }

        $msg = 'Missing params on check invoice compliance #' . $trans_no;
        Log::error($msg);
        return $hl->code(401)->msg($msg)->res();
    }

    public function updateErpOnboarding(Request $req) {
        $hl = new Helper;
        $business = (new Business)->getBusiness($req->header('X-PREFIX'));
        $status = true;
        if($business) {
            if($business->updateOnboardingStatus($status)) {
                $business->erp_onboarding_status = $status;
                $business->save();

                return $hl->code(200)->msg('Status updated')->res();
            }
        }
        return $hl->code(401)->msg('Error occured')->res();
    }
}