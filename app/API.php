<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class API extends Model
{
	private $business;

	public function getBusiness() {
        return $this->business;
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

        dd($csr);
        $response = $client->request('POST', 'compliance', ['json' => $form_params]);
        $data = $response->getBody();
        $output = json_decode($data->getContents(), 1);

        dd($output);

        $requestID = $output['Request ID'] ?? null;
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
        }

        return $requestID;
    }

    public function issueProductionCertificate(string $request_id) {
    	if(!$this->business) {
    		return null;
    	}

    	$form_params = [
    		"compliance_request_id"=> $this->business->getCCSID('request_id'),
    	];

    	$headers = [
           "Accept"=>"application/json",
           "Content-Type"=>"application/json",
           "Accept-Version"=>"V2",
           "OTP"=>$this->business->otp
    	];

    	$client = new \GuzzleHttp\Client([
            'base_uri'	=> env('ZATCA_SANDBOX_URL'),
            'headers' 	=> $headers
        ]);

        $response = $client->request('POST', 'compliance', $form_params);
        $data = $response->getBody();
        $output = json_decode($data->getContents(), 1);
        $requestID = $output['Request ID'] ?? null;
        $CCSIDbase64 = $output['binarySecurityToken'];
        $CCSIDSecret = $output['secret'];

        if($requestID) {
        	$this->business->ccsid = $CCSIDbase64;
        	$this->business->save();
        }

        return $requestID;
    }

    public function toBase64($str) {
    	return base64_encode($str);
    }

}