<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class ReportingLog extends Model
{
	protected $table = "reporting_log";
    protected $guarded = [];
    public $timestamps = false;

    public static $actions = [
        'Compliance',
        'Reporting',
        'Clearance'
    ];

    public function business() {
        return $this->belongsTo(Business::class);
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
    
    public static function addLog($action='Reporting', $business, $invoice_id, $trans_no, $api_response=null) {
        $item = self::create([
            'action'        => $action,
            'business_id'   => $business->id,
            'invoice_id'    => $invoice_id,
            'trans_no'      => $trans_no,
            'api_response'  => json_encode($api_response),
            'created_at'    => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        return $item;
    }
}
