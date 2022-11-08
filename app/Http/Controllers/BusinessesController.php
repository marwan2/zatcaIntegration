<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;

class BusinessesController extends Controller
{
	public function __construct() {
        $businesses = Business::orderBy('id', 'ASC')->get();
        view()->share('businesses', $businesses);
    }

    public function index(Request $req) {
        $businesses = new Business;
        $businesses = $businesses->paginate(10);

        return view('businesses.index', compact('businesses'));
    }

    public function create() {
        return view('businesses.create');
    }

    public function store(Request $req) {
        $this->validate($req, Business::$rules);

        $data = $req->all();
        $business = Business::create($data);
        
        session()->flash('flash_message', 'Business created successfully');
        return redirect()->to('businesses');
    }

    public function edit($id) {
        $business = Business::findOrFail($id);
        return view('businesses.edit', compact('business'));
    }

    public function update($id, Request $req) {
        $this->validate($req, Business::$rules);
        $data = $req->all();
        $record = Business::findOrFail($id);
        $record->update($data);

        session()->flash('flash_message', 'Business updated successfully');
        return redirect('businesses');
    }

    public function invoices(Request $req) {
        $invoices = null;
        $business = null;
        if($req->has('business_id')) {
            $business = Business::findOrFail($req->get('business_id'));

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
                $response = $client->request('GET', 'sales/10?page=1&limit=50');
                $data = $response->getBody();
                $invoices = json_decode($data->getContents(), 1);
                $invoices = $invoices['data']['list'] ?? [];
            } catch (\Exception $e) {
                dd($e);
            }
        }

    	return view('businesses.invoices', compact('invoices', 'business'));
    }

    public function generateXML(Request $req, $business_id, $trans_no) {
        $invoice = null;
        if($business_id && $trans_no) {
            $business = Business::findOrFail($business_id);
            $invoice = \App\Invoice::getInvoice($trans_no, $business);

            $invoiceDB = \App\Invoice::whereTrans_no($trans_no)->whereBusiness_id($business->id)->whereTrans_type('invoice')->first();
            if(!$invoiceDB) {
                $invoiceDB = \App\Invoice::create([
                    'trans_type'=>'invoice',
                    'trans_no'=>$trans_no,
                    'business_id'=>$business->id,
                    'uuid'=>\App\Invoice::generateUUID(),
                ]);
            }

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business);
            $inv->setInvoice($invoice);
            $inv->setInvoiceInDB($invoiceDB);
            $inv->setNo($trans_no);

            $xml = $inv->getXML();

            if($req->has('xml')) {
                return $xml;
            }

            if($req->has('base64')) {
                return base64_encode($xml);
            }

            if($req->has('file')) {
                $file = $inv->fileName();
                $path = public_path(\App\SimpleInvoice::$base_dir) . '/'.$file;
                $dom = new \DOMDocument;
                $dom->loadXML($xml);
                $dom->save($path);

                $fileAnchor = $inv->fileAnchor($file);
                return $fileAnchor;
                //return response($path, 200)->header('Content-Type', 'application/xml');
            }
        }
        return $invoice;
    }

    public function validateXML(Request $req) {
        $file = $req->get('file');
        $path = public_path(\App\SimpleInvoice::$base_dir) . '/'.$file;

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

    public function getTemplate($business_id, $trans_no) {
        $invoice = null;
        if($business_id && $trans_no) {
            $business = Business::findOrFail($business_id);
            $invoice = \App\Invoice::getInvoiceTemplate($trans_no, $business);
            print $invoice;
        }
        return $invoice;
    }

    public function getPDF($trans_no, $business_id) {
        if($business_id && $trans_no) {
            $business = Business::findOrFail($business_id);
            $invoice = \App\Invoice::getInvoiceTemplate($trans_no, $business);
            
            $inv = new \App\Invoice;
            $inv->setBusiness($business);

            $pdf = \PDF::loadHTML($invoice);
            return $pdf->save($inv->pdf_filename($trans_no));
        }
        return '';
    }

    public function showInvoice($business_id, $trans_no) 
    {
        if($business_id && $trans_no) {
            $business = Business::findOrFail($business_id);
            $invoice = \App\Invoice::getInvoice($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            $qr = new \App\ZatcaQR(
                $invoice['cust_ref'], 
                strval($invoice['customer']['tax_id']), 
                $invoice['order_date'], 
                $invoice['display_total'], 
                $invoice['tax_total']
            );

            $qrCode = '';
            $qrCode = $qr->getQRCode();
            return view('businesses.invoice', compact('invoice', 'business', 'qrCode'));
        }
        return 'Missing params.';
    }

    public function reporting(Request $req, $business_id, $trans_no) {
        if($business_id && $trans_no) {
            $business = Business::findOrFail($business_id);
            $invoice = \App\Invoice::getInvoice($trans_no, $business);
            $invoice['trans_no'] = $trans_no;

            // Generate
            $invoiceDB = \App\Invoice::whereTrans_no($trans_no)->whereBusiness_id($business->id)->whereTrans_type('invoice')->first();
            if(!$invoiceDB) {
                $invoiceDB = \App\Invoice::create([
                    'trans_type'=>'invoice',
                    'trans_no'=>$trans_no,
                    'business_id'=>$business->id,
                    'uuid'=>\App\Invoice::generateUUID(),
                ]);
            }

            $inv = new \App\XMLInvoice;
            $inv->setBusiness($business);
            $inv->setInvoice($invoice);
            $inv->setInvoiceInDB($invoiceDB);
            $inv->setNo($trans_no);
            $xml = $inv->getXML();

            // Save XML file
            $file = $inv->fileName();
            $invoice_path = public_path(\App\SimpleInvoice::$base_dir) . '/'.$file;
            $dom = new \DOMDocument;
            $dom->loadXML($xml);
            $dom->save($invoice_path);

            dd($invoice_path);
            // Sign
            exec('fatoora -invoice ' . $invoice_path .' -sign', $output);
            dd($output);

            // Call reporting API
        }
        return 'Missing params.';
    }
}