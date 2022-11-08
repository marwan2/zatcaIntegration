<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use PDF;

class Invoice extends Model
{
    protected $table = "invoices";
    protected $guarded = [];
    public $business;

    // Invoice Type codes for KSA
    const INVOICE = 388;
    const CREDIT_NOTE = 381;
    const DEBIT_NOTE = 383;
    const SELF_BILLING_INVOICE = 389;

    public function getBusiness() {
        return $this->business;
    }

    public function setBusiness($business) {
        $this->business = $business;
    }

    public function url($trans_no) {
        return url("invoices/{$this->business->id}/show/{$trans_no}");
    }

    public function pdf_url($trans_no) {
        return url("invoices/{$trans_no}/{$this->business->id}/pdf");
    }

    public function xml_url($trans_no) {
        return url('invoices/'.$this->business->id.'/xml/'.$trans_no.'?xml');
    }

    public function xml_file_url($trans_no) {
        return url('invoices/'.$this->business->id.'/xml/'.$trans_no.'?file');
    }

    public function encode_xml_url($trans_no) {
        return url('invoices/'.$this->business->id.'/xml/'.$trans_no.'?base64');
    }

    public function view_url($trans_no) {
        return url('invoices/'.$this->business->id.'/xml/'.$trans_no);
    }

    public function template_url($trans_no) {
        return url('invoices/'.$this->business->id.'/'.$trans_no);
    }

    public function reporting_url($trans_no) {
        return url('invoices/'.$this->business->id.'/reporting/'.$trans_no);
    }

    public function pdf_filename($trans_no, $with_path=true) {
        $inc = md5($this->business->id);
        $name = "pdf{$inc}_invoice_{$trans_no}.pdf";
        if($with_path) {
            $name = public_path('temp/'.$name);
        }
        return $name;
    }

    public static function getInvoice($trans_no, $business=null) {
    	if(!$business) {
    		return null;
    	}

    	$client = new \GuzzleHttp\Client([
            'base_uri'=> env('FA_API_URL'),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'X-PREFIX' => $business->xprefix,
                'AUTH-TOKEN' => $business->getAuthToken(),
            ]
        ]);

        $response = $client->request('GET', 'sales/'.$trans_no.'/10');
        $data = $response->getBody();
        $invoice = json_decode($data->getContents(), 1);
        $invoice = $invoice['data'] ?? null;
        return $invoice;
    }

    public static function getInvoiceTemplate($trans_no, $business=null) {
        if(!$business) {
            return null;
        }

        $client = new \GuzzleHttp\Client([
            'base_uri'=> env('FA_API_URL'),
            'headers' => [
                'Content-Type' => '*/*',
                'accept' => '*/*',
                'X-PREFIX' => $business->xprefix,
                'AUTH-TOKEN' => $business->getAuthToken(),
            ]
        ]);

        $response = $client->request('GET', 'sales/print_preview/'.$trans_no);
        $data = $response->getBody();
        $invoice = $data->getContents();
        return $invoice;
    }

    public static function getTaxforItems($business=null) {
        if(!$business) {
            return null;
        }

        $client = new \GuzzleHttp\Client([
            'base_uri'=> env('FA_API_URL'),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'X-PREFIX' => $business->xprefix,
                'AUTH-TOKEN' => $business->getAuthToken(),
            ]
        ]);

        $response = $client->request('GET', 'inventory/taxforitems');
        $data = $response->getBody();
        $res = json_decode($data->getContents(), 1);
        $res = $res['data'] ?? null;
        return $res;
    }

    public function saveInvoicePDF($trans_no) {
        if($this->business) {
            $invoice = self::getInvoiceTemplate($trans_no, $this->business);

            $path = $this->pdf_filename($trans_no);
            $pdf = \PDF::loadHTML($invoice);
            $pdf->save($path);
            return $path;
        }
        return '';
    }

    public static function generateUUID($str=null) {
        return Uuid::uuid4();
    }

    public static function docRefUUID($trans_no) {
        return (intval($trans_no) * 759) - 18;
    }

    // 01 for tax invoice
    // 02 for simplified tax invoice
    public static function getTypeCodeName($subtype='01') {
        if($subtype == '01') {
            return ['name'=>'0100000'];
        }

        if($subtype == '02') {
            return ['name'=>'0200000'];
        }
        return null;
    }
}
