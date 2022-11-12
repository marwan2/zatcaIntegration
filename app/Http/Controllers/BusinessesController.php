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
        
        session()->flash('flash_message', 'Business created successfully');
        return redirect()->to('businesses');
    }

    public function show($id) {
        $business = Business::findOrFail($id);

        // Set CSR config file
        $csr_content = $business->getCsrContent();

        $privateKey = $business->getPrivateKey();

        $result = $business->generateCSR($csr_content, $privateKey);

        if($result)
        {
            $api = new \App\API;
            $api->setBusiness($business);

            $api->issueComplianceCertificate();

            $api->issueProductionCertificate();
        }

        dd($csr);
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
}