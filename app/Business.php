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
        'organization_unit_name'=>'required|max:200',
        'country_iso2'=>'required|max:2',
        'location_address'=>'required',
        'xprefix'=>'required|numeric',
    ];

    public static $scheme = [
		'CRN'=>'Commercial Registration number',
		'MOM'=>'Momra license',
		'MLS'=>'MLSD license',
		'SAG'=>'Sagia license',
		'OTH'=>'Other',
	];

	public function getAuthToken() {
		$authToken = ($this->startsWith($this->auth_token, 'Bearer')) ? $this->auth_token: 'Bearer '.$this->auth_token;
		return $authToken;
	}

	public function startsWith($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    /*
        csr.common.name=TST-886431145-312345678900003
        csr.serial.number=1-TST|2-TST|3-ed22f1d8-e6a2-1118-9b58-d9a8f11e445f
        csr.organization.identifier=312345678900003
        csr.organization.unit.name=3123456789
        csr.organization.name=3123456789
        csr.country.name=SA
        csr.invoice.type=1111
        csr.location.address=TST
        csr.industry.business.category=TST
    */
    public function getCsrContent() {
        $common_name = 'McLedger Invoicing App';
        $serial_number = '1-McLedger|2-InvoicingApp|2.0.0';
        $invoice_type = '1000'; // Invoicing App generate only Standard invoices, no simpliefied invoices supported
        $location = 'DubaiUAE';
        $business_category = 'Bookkeeping Company';
        $iso2 = 'SA';
        $email = 'info@mcledger.co';

        $trn = $this->trn;
        $organization_unit_name = $this->organization_unit_name ?? $this->name;
        $taxpayer_name = $this->name;

        $fields = [
            'commonName'=>$common_name,
            'serialNumber'=>$serial_number,
            'organizationIdentifier'=>$trn,
            'organizationalUnitName'=>$organization_unit_name,
            'organizationName'=>$taxpayer_name,
            'countryName'=>$iso2,
            'invoiceType'=>$invoice_type,
            'localityName'=>$location,
            'industryBusinessCategory'=>$business_category,
            'distinguished_name'=>'req_distinguished_name',
        ];
        
        $dn = array(
            "countryName" => $iso2,
            "stateOrProvinceName" => $this->city,
            "localityName" => $location,
            "organizationName" => $taxpayer_name,
            "organizationalUnitName" => $organization_unit_name,
            "commonName" => $common_name,
            "emailAddress" => $email,
        );

        return $dn;
    }

    public function generateCSR($dn, $private_key) {
        // Generate CSR
        $csr = openssl_csr_new($dn, $private_key, array('digest_alg' => 'sha256'));

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

        return true;
    }

    public function getPrivateKey() {
        $name = 'privatekey.pem';
        $contents = storage_path('app/'.$name);
        $key = file_get_contents($contents);

        try {
            return openssl_pkey_get_private($key);
        } catch(\Exception $e) {
            return null;
        }
        return null;
    }

    public function getCSR() {
        $csr_file = storage_path('app/'.$this->xprefix.'_csr.pem');
        if(Storage::exists($csr_file)) {
            $content = file_get_contents($csr_file);
            $content = $this->cleanUp($content);
            return $content;
        }
        return null;
    }

    public function getCCSID($key=null) {
        if($this->ccsid) {
            $decoded_ccsid = json_decode($this->ccsid);
            if($key) {
                return $decoded_ccsid[$key];
            }
            return $decoded_ccsid;
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
}
