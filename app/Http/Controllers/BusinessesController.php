<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;

class BusinessesController extends Controller
{
	public function __construct() {
        
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
        
        session()->flash('flash_message', 'Business created successfully, you can start onboarding process.');
        return redirect()->to('businesses/'.$business->id);
    }

    /*
        Show Business Integration Details
    */
    public function show($id) {
        $business = Business::findOrFail($id);
        return view('businesses.show', compact('business'));
    }

    /*
        Zatca Business Onboarding
        - Generate CSR
        - Obtain Compliance CSID
        - Obtain Production CSID
    */
    public function onboarding(Request $req, $id) {
        $business = Business::findOrFail($id);
        $result = true;
        if(!$business->csrExists()) {
            // Set CSR config file
            $csr_content = $business->getCsrContent();
            $privateKey = $business->getPrivateKey();
            $result = $business->generateCSR($csr_content, $privateKey);
        }

        if($result) {
            $api = new \App\API;
            $api->setBusiness($business);
            $api->issueComplianceCertificate();
            $api->issueProductionCertificate();
        }

        if($req->ajax()) {
            return json_encode([
                'status'=>true, 
                'message'=>'Business onboarding process completed successfully.'
            ]);
        }
        dd($result);
    }

    /*
        Zatca Production Certificate Renewal
    */
    public function certificateRenewal($business_id) {
        $business = Business::findOrFail($business_id);

        $api = new \App\API;
        $api->setBusiness($business);
        $result = $api->productionCertificateRenewal();
        return $result;
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

    public function generateCertPem($business_id) {
        $business = Business::findOrFail($business_id);

        $CCSIDbase64 = $business->getCCSID('ccsid');
        $PCSIDbase64 = $business->getPCSID('binarySecurityToken');

        if(!$CCSIDbase64) { 
            throw new \Exception('Compliance CSID is missing');
        }
        if(!$PCSIDbase64) { 
            throw new \Exception('Production CSID is missing');
        }

        $output = $business->generateCertificatePEM($CCSIDbase64, 'compliance');
        $output = $business->generateCertificatePEM($PCSIDbase64, 'production');
        return $output;
    }
}