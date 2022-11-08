<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
	protected $table = 'businesses';
    protected $guarded = [];

    public static $rules = [
        'name'=>'required',
        'legal_registration_name'=>'required',
        'serial_number'=>'required',
        'trn'=>'required|max:15|starts_with:3',
        'organization_unit_name'=>'required|max:200',
        'country_iso2'=>'required|max:2',
        'invoice_type'=>'required',
        'location_address'=>'required',
        'business_category'=>'required',
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
}
