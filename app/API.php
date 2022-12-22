<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\Invoice;

class API extends Model
{
	private $business;
    private $invoice_db;

	public function getBusiness() {
        return $this->business;
    }

    public function setInvoice(\App\Invoice $invoice) {
        $this->invoice = $invoice;
    }

    public function setBusiness(\App\Business $business) {
        $this->business = $business;
    }

	public function issueComplianceCertificate() {
        if(!$this->business) {
    		return null;
    	}

    	$csr = $this->toBase64($this->business->getCSR());
    	$form_params = [
    		"csr"=> $csr,
    	];

    	$headers = [
           "Accept"=>"application/json",
           "Accept-Version"=>"V2",
           "OTP"=>$this->business->otp
    	];

    	$client = new \GuzzleHttp\Client([
            'base_uri'	=> env('ZATCA_SANDBOX_URL'),
            'headers' 	=> $headers
        ]);

        $response = $client->request('POST', 'compliance', ['json' => $form_params]);
        $data = $response->getBody();
        $output = json_decode($data->getContents(), 1);

        if($response->getStatusCode() == 400) {
            dd($output);
        }

        $requestID = $output['requestID'] ?? null;
        $CCSIDbase64 = $output['binarySecurityToken'];
        $CCSIDSecret = $output['secret'];

        if($requestID) {
        	$ccsid = [
        		'request_id'=>$requestID,
        		'secret'=>$CCSIDSecret,
        		'ccsid'=>$CCSIDbase64,
        	];

        	$this->business->ccsid = json_encode($ccsid);
        	$this->business->save();
            $this->business->generateCertificatePEM($CCSIDbase64, 'compliance');
        } else {
            throw new \Exception("Error occured while obtaining a Compliance CSID (CCSID).");
        }

        return $requestID;
    }

    public function issueProductionCertificate() {
    	if(!$this->business) {
    		return null;
    	}

        $request_id = $this->business->getCCSID('request_id');
        $CCSID = $this->business->getCCSID('ccsid');
        $secret = $this->business->getCCSID('secret');

        if(!$request_id) {
            throw new \Exception("Request ID is missing");
        }

    	$form_params = [
    		"compliance_request_id"=> $request_id,
    	];

        $authToken = $CCSID . ":" . $secret;
        $BasicAuthToken = "Basic " . $this->toBase64($authToken);

    	$headers = [
           "Accept"=>"application/json",
           "Accept-Version"=>"V2",
           "Content-Type"=>"application/json",
           "Authorization"=>$BasicAuthToken,
    	];

    	$client = new \GuzzleHttp\Client([
            'base_uri'	=> env('ZATCA_SANDBOX_URL'),
            'headers' 	=> $headers
        ]);

        $response = $client->request('POST', 'production/csids', ['json' => $form_params]);
        $data = $response->getBody();
        $output = json_decode($data->getContents(), 1);

        if(isset($output['requestID']) && isset($output['binarySecurityToken'])) {
            $pcsid = $output;
        	$this->business->pcsid = json_encode($pcsid);
        	$this->business->save();

            $PCSIDbase64 = $this->business->getPCSID('binarySecurityToken');
            $this->business->generateCertificatePEM($PCSIDbase64, 'production');
        } else {
            throw new \Exception("Obtaining production certificate failed for business " . $this->xprefix);
        }

        return $output;
    }

    public function productionCertificateRenewal() {
        if(!$this->business) {
            return null;
        }

        $csr = $this->toBase64($this->business->getCSR());
        $form_params = [
            "csr"=> $csr,
        ];

        if(!$csr) {
            throw new \Exception("CSR is missing");
        }

        $CCSID = $this->business->getCCSID('ccsid');
        $secret = $this->business->getCCSID('secret');
        $authToken = $CCSID . ":" . $secret;
        $BasicAuthToken = "Basic " . $this->toBase64($authToken);

        $headers = [
           "Accept"=>"application/json",
           "Content-Type"=>"application/json",
           "Accept-Version"=>"V2",
           "Authorization"=>$BasicAuthToken,
           "OTP"=>$this->business->otp,
        ];

        $client = new \GuzzleHttp\Client([
            'base_uri'  => env('ZATCA_SANDBOX_URL'),
            'headers'   => $headers
        ]);

        $response = $client->request('PATCH', 'production/csids', ['json' => $form_params]);
        $data = $response->getBody();
        $output = json_decode($data->getContents(), 1);

        if(isset($output['requestID']) && isset($output['binarySecurityToken'])) {
            $pcsid = $output;
            $this->business->pcsid = json_encode($pcsid);
            $this->business->save();
        } else {
            throw new \Exception("Renewal production certificate process failed for business " . $this->xprefix);
        }

        return $output;
    }

    /**
     * Reporting Single Invoice to ZATCA
     * @param $signed_invoice_encoded: Encoded in base64
     * @param $invoice_hash
    */
    public function reporting($signed_invoice_encoded, $invoice_hash) {
        if(!$this->business) {
            return null;
        }

        if(!$this->business->getPCSID()) {
            throw new \Exception("Production certificate is missing");
        }

        $clearanceStatus = 0;
        $headers = [
           "Accept"           => "application/json",
           "Content-Type"     => "application/json",
           "Accept-Language"  => "en",
           "Accept-Version"   => "V2",
           "Authorization"    => $this->getBasicAuthToken(),
           "Clearance-Status" => $clearanceStatus,
        ];

        $client = new \GuzzleHttp\Client([
            'base_uri'  => env('ZATCA_SANDBOX_URL'),
            'headers'   => $headers
        ]);

        $form_params = [
            'uuid'        => env('EGS_UUID'),
            'invoiceHash' => $invoice_hash,
            'invoice'     => $signed_invoice_encoded,
        ];
        
        $output = null;
        try {
            $response = $client->request('POST', 'invoices/reporting/single', ['json' => $form_params]);
            $data = $response->getBody();
            $output = json_decode($data->getContents(), 1);
        } catch(\Exception $e) {
            if($e != null) {
                //print($e->getResponse()->getBody()->getContents());
                $output = $e->getResponse()->getBody()->getContents() ?? null;
            }
            $msg = 'Error reporting invoice';
            //throw new \Exception($msg);
            \Log::error($e->getMessage() ?? $msg);
        }

        return $output;
    }

    /**
     * Check Invoice Compliance at ZATCA
     * @param $signed_invoice_string
     * @param $invoice_hash
     * @param $egs_uuid: The Invoice Unit Device UUID
    */
    public function invoiceCompliance($signed_invoice_string, $invoice_hash) {
        if(!$this->business) {
            return null;
        }

        if(!$this->business->getCCSID()) {
            throw new \Exception("Compliance certificate is missing");
        }

        $headers = [
           "Accept"           => "application/json",
           "Content-Type"     => "application/json",
           "Accept-Language"  => "en",
           "Accept-Version"   => "V2",
           "Authorization"    => $this->getBasicAuthToken('compliance'),
        ];

        $client = new \GuzzleHttp\Client([
            'base_uri'  => env('ZATCA_SANDBOX_URL'),
            'headers'   => $headers
        ]);

        $form_params = [
            'uuid'        => env('EGS_UUID'),
            'invoiceHash' => $invoice_hash,
            'invoice'     => $signed_invoice_string,
        ];

        $output = null;
        try {
            $response = $client->request('POST', 'compliance/invoices', ['json' => $form_params]);
            $data = $response->getBody();
            $output = json_decode($data->getContents(), 1);
        } catch(\Exception $e) {
            if($e != null) {
                $output = $e->getResponse()->getBody()->getContents() ?? null;
            }
            \Log::error($e->getMessage());
        }
        return $output;
    }

    /**
     * Get ZATCA Authorization AuthToken 
     */
    public function getBasicAuthToken($type='production') {
        $token = '';
        $secret = '';
        if($type == 'compliance') {
            $token = $this->business->getCCSID('ccsid');
            $secret = $this->business->getCCSID('secret');
        } else {
            $token = $this->business->getPCSID('binarySecurityToken');
            $secret = $this->business->getPCSID('secret');
        }

        $authToken = $token . ":" . $secret;
        return "Basic " . $this->toBase64($authToken);
    }

    public function toBase64($str) {
    	return base64_encode($str);
    }

}