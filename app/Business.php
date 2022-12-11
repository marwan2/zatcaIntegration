<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Business extends Model
{
	protected $table = 'businesses';
    protected $guarded = [];

    public static $rules = [
        'name'=>'required',
        'legal_registration_name'=>'required',
        'trn'=>'required|max:15|starts_with:3',
        'country_iso2'=>'required|max:2',
        'location_address'=>'required',
        'xprefix'=>'required|numeric',
        'auth_token'=>'required',
    ];

    public static $api_validation = [
        'name'=> 'required',
        'legal_registration_name'=> 'required',
        'trn'=>'required|max:15|starts_with:3',
        'country_iso2'=> 'required',
        'location_address'=> 'required',
        'street_name'=> 'required',
        'city'=> 'required',
        'building_no'=> 'required',
        'country_subentity'=> 'required',
        'district'=> 'required',
        'postal_code'=> 'required',
        'identification_scheme'=> 'required',
        'identification_id'=> 'required',
    ];

    public static $scheme = [
		'CRN'=>'Commercial Registration number',
		'MOM'=>'Momra license',
		'MLS'=>'MLSD license',
		'SAG'=>'Sagia license',
		'OTH'=>'Other',
	];

    public function getBusiness($prefix) {
        $business = self::where('xprefix', $prefix)->first();
        return $business;
    }

    public function createBusiness($data) {
        $record = self::create([
            'name'=>$data['name'],
            'legal_registration_name'=>$data['name'],
            'country_iso2'=>$data['iso2'],
            'location_address'=>$data['address'],
            'xprefix'=>$data['ERP_xPrefix'],
            'auth_token'=>$data['ERP_AuthToken'],
            'street_name'=>$data['street_name'],
            'city'=>$data['city'],
            'building_no'=>$data['building_no'],
            'country_subentity'=>$data['country_subentity'],
            'district'=>$data['district'],
            'postal_code'=>$data['postal_code'],
            'identification_scheme'=>$data['identification_scheme'],
            'identification_id'=>$data['identification_id'],
        ]);
        return $record;
    }

    public function alreadyOnboarded($data) {
        $check = Business::where('xprefix', $data['xprefix'])->whereNotNull('ccsid')->whereNotNull('pcsid')->first();
        if($check && $check->count() > 0) {
            return true;
        }
        return false;
    }

	public function getAuthToken() {
		$authToken = ($this->startsWith($this->auth_token, 'Bearer')) ? $this->auth_token: 'Bearer '.$this->auth_token;
		return $authToken;
	}

	public function startsWith($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public function getCsrContent() {
        $common_name = 'McLedger Invoicing App';
        $serial_number = '1-McLedger|2-Bookkeeping|3-2.2.35';
        $invoice_type = '1000'; // Invoicing App generate only Standard invoices, no simpliefied invoices supported
        $location = 'DubaiUAE';
        $business_category = 'Bookkeeping Company';
        $iso2 = 'SA';
        $email = 'info@mcledger.co';
        $regAddress = "https://mcledger.co";

        $trn = $this->trn;
        $organization_unit_name = $this->organization_unit_name ?? $this->name;
        $taxpayer_name = $this->name;
        
        $dn = array(
            "countryName" => $iso2,
            "stateOrProvinceName" => $this->city,
            "localityName" => $location,
            "organizationName" => $taxpayer_name,
            "organizationalUnitName" => $organization_unit_name,
            "commonName" => $common_name,
            "emailAddress" => $email,
            "title"=>$invoice_type,
            "registeredAddress"=>$regAddress,
            "businessCategory"=>$business_category,
            "UID"=>$trn,
            "SN"=>$serial_number,
        );

        $filename = 'zatca.cnf';
        $config_template = 'zatca_template_config.cnf';

        if (Storage::disk('local')->exists($config_template)) {
            $content = Storage::disk('local')->get($config_template);

            $content = str_replace('_CommonName_', $common_name, $content);
            $content = str_replace('_emailHere_', $email, $content);
            $content = str_replace('_Country_', $iso2, $content);
            $content = str_replace('_OrgnizationName_', $organization_unit_name, $content);
            $content = str_replace('_OrganiztionUnit_', $taxpayer_name, $content);
            $content = str_replace('_SerialNumber_', $serial_number, $content);
            $content = str_replace('_TRN_', $trn, $content);
            $content = str_replace('_RegAddress_', $regAddress, $content);
            $content = str_replace('_BusinessCategory_', $business_category, $content);
            $content = str_replace('_InvoiceType_', $invoice_type, $content);

            Storage::disk('local')->put($filename, $content);
        } else {
            throw new \Illuminate\Contracts\Filesystem\FileNotFoundException(sprintf('File not found: %s', $filename), 404);
        }

        return $dn;
    }

    public function generateCSR($dn, $private_key) {
        $csr_file = storage_path('app/'.$this->xprefix.'_cert.csr');
        $cnf_file = storage_path('app/zatca.cnf');

        exec("openssl req -new -sha256 -key ".$private_key." -extensions v3_req -config ".$cnf_file." -out ".$csr_file);
        return true;

        // Generate CSR
        /*$configargs = array(
            'digest_alg' => 'sha256WithRSAEncryption'
        );
        $configArgs = array("x509_extensions" => "v3_req");
        $csr = openssl_csr_new($dn, $private_key, $configArgs);

        // Generate a self-signed cert, valid for 365 days
        $days = 365;
        $x509 = openssl_csr_sign($csr, null, $private_key, $days, array('digest_alg' => 'sha256'));

        // Save CSR file
        $csr_file = storage_path('app/'.$this->xprefix.'_csr.pem');
        if(!Storage::exists($csr_file)) {
            Storage::put($csr_file, '');
        }

        openssl_csr_export_to_file($csr, $csr_file);

        // Save signed certificate to disk
        $cert_filename = storage_path('app/'.$this->xprefix.'_cert.pem');
        if(!Storage::exists($cert_filename)) {
            Storage::put($cert_filename, '');
        }
        openssl_x509_export_to_file($x509, $cert_filename);
        return true;*/
    }

    /*
        Generate certificate PEM file (Compliance, Production)
    */
    public function generateCertificatePEM($CCSIDbase64, $type='production') {
        $CCSID = base64_decode($CCSIDbase64);
        $CCSIDCertString = "-----BEGIN CERTIFICATE-----\n" . $CCSID . "\n" . "-----END CERTIFICATE-----";

        $csid_pem_file = "{$this->xprefix}_{$type}_cert.pem";
        Storage::disk('local')->put($csid_pem_file, $CCSIDCertString);

        return $CCSIDCertString;
    }

    public function getPrivateKey() {
        $name = 'privatekey.pem';
        $key_file = storage_path('app/'.$name);
        return $key_file;

        $key = file_get_contents($key_file);

        try {
            return openssl_pkey_get_private($key);
        } catch(\Exception $e) {
            return null;
        }
        return null;
    }

    public function getCSR() {
        $csr_file = $this->xprefix.'_cert.csr';
        if(Storage::disk('local')->exists($csr_file)) {
            $content = Storage::disk('local')->get($csr_file);
            return $content;
        }
        return null;
    }

    public function csrExists() {
        $csr_file = $this->xprefix.'_cert.csr';
        if(Storage::disk('local')->exists($csr_file)) {
            return true;
        }
        return false;
    }

    // Compliance Certificate
    public function getCCSID($key=null) {
        if($this->ccsid) {
            $decoded_ccsid = json_decode($this->ccsid);
            if($key) {
                return $decoded_ccsid->$key;
            }
            return $decoded_ccsid;
        }
        return null;
    }

    // Production Certificate
    public function getPCSID($key=null) {
        if($this->pcsid) {
            $decoded_pcsid = json_decode($this->pcsid);
            if($key) {
                return $decoded_pcsid->$key;
            }
            return $decoded_pcsid;
        }
        return null;
    }

    public function cleanUp($str) {
        //$str = str_replace('-----BEGIN CERTIFICATE-----', '', $str);
        //$str = str_replace('-----END CERTIFICATE-----', '', $str);
        $str = str_replace('\r\n', '', $str);
        $searches = array("\r", "\n", "\r\n");

        // Replace the line breaks with a space
        $string = str_replace($searches, "", $str);

        // Replace multiple spaces with one
        $output = preg_replace('!\s+!', '', trim($string));

        return $output;
    }

    /**
     * Update ERP database with needed columns for ZATCA integration
     * Such as reporting status column in debtor_trans
     */
    public function updateERPDB($business=null) {
        $client = new \GuzzleHttp\Client([
            'base_uri'=> env('FA_API_URL'),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'X-PREFIX' => $this->xprefix,
                'AUTH-TOKEN' => $this->getAuthToken(),
            ]
        ]);

        try {
            $response = $client->request('GET', 'company/zatca-db-updates');
            $body = $response->getBody();
            $res = json_decode($body->getContents(), 1);
            return $res;
        } catch(\Exception $e) {
            \Log::error('Cannot update ERP DB for ' . $this->name ?? '');
            return false;
        }
        return null;
    }

    /**
     * Update onboarding Status on business at ERP
     */
    public function updateOnboardingStatus($status=null) {
        $client = new \GuzzleHttp\Client([
            'base_uri'=> env('FA_API_URL'),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'X-PREFIX' => $this->xprefix,
                'AUTH-TOKEN' => $this->getAuthToken(),
            ]
        ]);
        $data = ['status'=>$status];

        try {
            $response = $client->request('POST', 'zatca/update-onboarding-status', ['json' => $data]);
            $body = $response->getBody();
            $res = json_decode($body->getContents(), 1);
            return $res;
        } catch(\Exception $e) {
            \Log::error('Cannot update onboarding status for ' . $this->name ?? '');
            print($e->getResponse()->getBody()->getContents());
            return false;
        }
        return null;
    }

    public static function selected(\Illuminate\Http\Request $req) {
        if($req->session()->has('se_business')) {
            return $req->session()->get('se_business');
        }
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('businesses/select')->with('flash_message', 'Please select business.'));
    }
}
