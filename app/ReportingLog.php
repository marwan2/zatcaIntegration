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
    
    public static function addLog($action='Reporting', 
        $business,
        $invoice_id, 
        $trans_no,
        $api_response=null, 
        $trans_type='invoice', 
        $method="api"
    ) {
        $item = self::create([
            'method'        => $method,
            'action'        => $action,
            'trans_type'    => $trans_type ?? 'invoice',
            'business_id'   => $business->id,
            'invoice_id'    => $invoice_id,
            'trans_no'      => $trans_no,
            'api_response'  => json_encode($api_response),
            'created_at'    => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        return $item;
    }
}
