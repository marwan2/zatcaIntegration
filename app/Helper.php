<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
	public $status_code = 200;
	public $message = '';
	public $headers = [];
	const ERP_SALESINVOICE = 10;
	const ERP_CREDITNOTE = 11;

	public function code($status_code=200) {
		$this->status_code = $status_code;
		return $this;
	}

	public function msg($message='') {
		$this->message = $message;
		return $this;
	}

	public function header($headers=[]) {
		$this->headers = $headers;
		return $this;
	}

	public function res($data = []) {
		$message = $this->message;
		$code = $this->status_code;
		$headers = $this->headers;

        $response = compact('data', 'message', 'code');
        return response()->json($response, $code, $headers);
    }
}