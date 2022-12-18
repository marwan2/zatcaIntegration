<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;
use App\Invoice;
use App\CreditNote;
use App\Helper;

class CreditNotesController extends Controller
{
	public function __construct() {
        $businesses = Business::orderBy('id', 'ASC')->get();
        view()->share('businesses', $businesses);
    }

	public function index(Request $req) {
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
                $url = "customers/allocations?page=1&limit=30&type=".Helper::ERP_CREDITNOTE."&settled=1";
                $response = $client->request('GET', $url);
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

        return view('credit_notes.index', compact('invoices', 'business', 'paging'));
    }

    public function generateXML(Request $req, $trans_no) {
        $invoice = null;
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = CreditNote::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = Invoice::getInvoiceFromDb($trans_no, $business, 'credit_note');

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no)
                ->setTypeCode(Invoice::CREDIT_NOTE);

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

    public function getTemplate(Request $req, $trans_no) {
        $invoice = null;
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = Invoice::getInvoiceTemplate($trans_no, $business);
            print $invoice;
        }
        return;
    }

    public function getPDF(Request $req, $trans_no) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = Invoice::getInvoiceTemplate($trans_no, $business);
            $inv = new Invoice;
            $inv->setBusiness($business);

            $pdf = \PDF::loadHTML($invoice);
            return $pdf->save($inv->pdf_filename($trans_no));
        }
        return '';
    }

    public function showInvoice(Request $req, $trans_no) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = CreditNote::getInvoiceFromErp($trans_no, $business);
            $logs = \App\ReportingLog::whereBusiness_id($business->id)->whereTrans_no($trans_no)->paginate(10);
            $invoice['trans_no'] = $trans_no;

            $qr = new \App\ZatcaQR(
                $invoice['cust_ref'], 
                strval($invoice['customer']['tax_id']), 
                $invoice['order_date'], 
                $invoice['display_total'], 
                $invoice['tax_total']
            );

            $qrCode = $qr->getQRCode();
            return view('credit_notes.show', compact('invoice', 'business', 'qrCode', 'logs'));
        }
        return 'Missing params.';
    }

    /**
     * Check Credit Note Compliance 
     */
    public function checkInvoiceCompliance(Request $req, $trans_no=null) {
        $business = Business::selected($req);

        if($business && $trans_no) {
            $invoice = CreditNote::getInvoiceFromErp($trans_no, $business);
            $invoiceDB = Invoice::getInvoiceFromDb($trans_no, $business, 'credit_note');
            $invoice['trans_no'] = $trans_no;

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business)
                ->setInvoice($invoice)
                ->setInvoiceInDB($invoiceDB)
                ->setNo($trans_no)
                ->setTypeCode(Invoice::CREDIT_NOTE);
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
            \App\ReportingLog::addLog('Compliance', $business, $invoiceDB->id, $trans_no, $output);
            \Log::info($output);
            dd($output);
        }

        $msg = 'Missing params on check invoice compliance #' . $trans_no;
        \Log::error($msg);
        return $msg;
    }
}