<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;
use App\Helper;
use App\ReportingLog;

class LogsController extends Controller
{
	public function __construct() {

    }

    public function index(Request $req, $business_id=null) {
        $business = null;

        if($business_id) {
            $business = Business::findOrFail($business_id);
        } else {
            $business = Business::selected($req);
        }

        $logs = new ReportingLog;
        $logs = $logs->whereBusiness_id($business->id);
        
        if($req->has('method') && $req->get('method')) {
            $logs = $logs->where('method', $req->get('method'));
        }
        if($req->has('action') && $req->get('action')) {
            $logs = $logs->where('action', $req->get('action'));
        }
        if($req->has('q') && $req->get('q')) {
            $q = $req->get('q');
            $logs = $logs->where(function ($query) use ($q) {
                $query->where('id', $q)
                    ->orWhere('trans_no', 'LIKE', '%' . $q . '%')
                    ->orWhere('invoice_id', 'LIKE', '%' . $q . '%')
                    ->orWhere('xml', 'LIKE', '%' . $q . '%')
                    ->orWhere('api_response', 'LIKE', '%' . $q . '%');
            });
        }
        $logs = $logs->orderBy('created_at', 'DESC')->paginate(10);

    	return view('logs.index', compact('logs'));
    }
}