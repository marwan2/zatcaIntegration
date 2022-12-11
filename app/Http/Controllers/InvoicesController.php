<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;
use App\Invoice;
use App\Helper;

class InvoicesController extends Controller
{
	public function __construct() {

    }

    public function index() {
    	return view('invoices.index');
    }

	public function invoices(Request $req) {
        $invoices = null;
        $paging = null;
        $business = Business::selected($req);

        if($business) {
            $client = new \GuzzleHttp\Client([
                'base_uri'=> env('FA_API_URL'),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                    'X-PREFIX' => $business->xprefix,
                    'AUTH-TOKEN' => $business->getAuthToken(),
                ]
            ]);

            try {
                $response = $client->request('GET', 'sales/'.Helper::ERP_SALESINVOICE.'?page=1&limit=30');
                $data = $response->getBody();
                $data = json_decode($data->getContents(), 1);

                if($data) {
                    $invoices = $data['data']['list'] ?? [];
                    $paging = [
                        'next' => $data['data']['next_url'] ?? null,
                        'prev' => $data['data']['previous_url'] ?? null,
                        'total' => $data['data']['count_records'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                dd($e);
            }
        }

        return view('invoices.index', compact('invoices', 'business', 'paging'));
    }

    public function generateXML(Request $req, $trans_no) {
        $invoice = null;
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = Invoice::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = Invoice::getInvoiceFromDb($trans_no, $business);

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no)
                ->setTypeCode(Invoice::INVOICE);

            $xml = $inv->getXML();

            if($req->has('xml')) {
                if($req->has('signed') && $req->get('signed')) {
                    $signing = new \App\Signing;
                    $signing->setBusiness($business);
                    list($signed_invoice_string, $invoice_hash, $qr) = $signing->signInvoice($xml, false);

                    $path = $inv->xmlPath();
                    $dom = new \DOMDocument;
                    $dom->loadXML($signed_invoice_string);
                    $dom->save($path);
                    $xml = "<?xml version=\"1.0\" ?>\n" . $signed_invoice_string;
                    echo $xml; return;
                }
                return $xml;
            }

            if($req->has('base64')) {
                return base64_encode($xml);
            }

            if($req->has('file')) {
                $file = $inv->fileName();
                $path = public_path(\App\XMLInvoice::$base_dir) . '/'.$file;
                $dom = new \DOMDocument;
                $dom->loadXML($xml);
                $dom->save($path);

                $fileAnchor = $inv->fileAnchor($file);
                return $fileAnchor;
            }
        }
        return $invoice;
    }

    public function validateXML(Request $req) {
        $file = $req->get('file');
        $path = public_path(\App\XMLInvoice::$base_dir) . '/'.$file;
        $file_content = file_get_contents($path);

        $dom = new \DOMDocument;
        $inv = new \App\XMLInvoice;
        $dom->loadXML($file_content);

        $fileAnchor = $inv->fileAnchor($file);
        if($inv->isValidSchema($dom)) {
            return 'Valid document. ' . $fileAnchor;
        }
        return 'Document is not valid. ' . $fileAnchor;
    }

    public function getTemplate(Request $req, $trans_no) {
        $invoice = null;
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = \App\Invoice::getInvoiceTemplate($trans_no, $business);
            print $invoice;
        }
        return;
    }

    public function getPDF(Request $req, $trans_no) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = \App\Invoice::getInvoiceTemplate($trans_no, $business);
            
            $inv = new \App\Invoice;
            $inv->setBusiness($business);

            $pdf = \PDF::loadHTML($invoice);
            return $pdf->save($inv->pdf_filename($trans_no));
        }
        return '';
    }

    public function showInvoice(Request $req, $trans_no) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = \App\Invoice::getInvoiceFromErp($trans_no, $business);
            $logs = \App\ReportingLog::whereBusiness_id($business->id)->whereTrans_no($trans_no)->orderBy('created_at', 'DESC')->paginate(5);
            $invoice['trans_no'] = $trans_no;

            $qr = new \App\ZatcaQR(
                $invoice['cust_ref'], 
                strval($invoice['customer']['tax_id']), 
                $invoice['order_date'], 
                $invoice['display_total'], 
                $invoice['tax_total']
            );

            $qrCode = $qr->getQRCode();
            return view('invoices.show', compact('invoice', 'business', 'qrCode', 'logs'));
        }
        return 'Missing params.';
    }

    public function reporting(Request $req, $trans_no) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = \App\Invoice::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = \App\Invoice::getInvoiceFromDb($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no);
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
                    print ('Invoice compliance passed successfully');
                }
            } else {
                $msg = "Invoice is not complaint with ZATCA";
                \Log::warning($msg);
            }

            \App\ReportingLog::addLog('Reporting', $business, $invoiceDB->id, $trans_no, $output, 'app');
            \Log::info($output);
            dd($output);
        }

        $msg = 'Missing params on reporting invoice #' . $trans_no;
        \Log::error($msg);
        return $msg;
    }

    /**
     * Check Invoice Compliance 
     */
    public function checkInvoiceCompliance(Request $req, $trans_no=null) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = \App\Invoice::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = \App\Invoice::getInvoiceFromDb($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no);
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
            
            if (isset($output['validationResults']) && isset($output['validationResults']['status']) == 'PASS') {
                if(isset($output['clearanceStatus']) && $output['clearanceStatus'] == 'CLEARED') {
                    print ('Invoice compliance passed successfully');
                }
            } else {
                $msg = "Invoice is not complaint with ZATCA";
                \Log::warning($msg);
            }

            // Save action to log table
            \App\ReportingLog::addLog('Compliance', $business, $invoiceDB->id, $trans_no, $output, 'app');
            \Log::info($output);
            dd($output);
        }

        $msg = 'Missing params on check invoice compliance #' . $trans_no;
        \Log::error($msg);
        return $msg;
    }

	public function encodeFile(Request $req) {
		$file = $req->get('filename');
		$file_encoded = '';
		if($file) {
			$file_encoded = base64_encode(file_get_contents(public_path(XMLInvoice::$base_dir).'/'.$file));
		}
		return $file_encoded;
	}
}