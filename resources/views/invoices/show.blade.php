@php
	use App\Invoice;
	$inv = new Invoice;
	$inv->setBusiness($business);
@endphp
@extends('layouts.master')

@section('content')
	<a href="{{url('invoices')}}" class="btn btn-outline-dark">Back to Listing</a></h3>
	<hr>
	@if($invoice)
		<h3>Transaction #{{$invoice['trans_no']}}</h3>
		<table border="1" cellpadding="6" class="table table-bordered" style="border-collapse: collapse;">
			<thead>
				<tr>
					<td>ID</td>
					<td>Ref</td>
					<td>Customer</td>
					<td>Date</td>
					<td>Due Date</td>
					<td>SubTotal</td>
					<td>Total</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>{{$invoice['trans_no']}}</td>
					<td>{{$invoice['ref']}}</td>
					<td>{{$invoice['debtor_ref'] ?? $invoice['customer']['debtor_ref']}}</td>
					<td>{{$invoice['order_date']}}</td>
					<td>{{$invoice['due_date']}}</td>
					<td>{{$invoice['sub_total']}} {{$invoice['customer']['curr_code']}} </td>
					<td>{{$invoice['display_total']}} {{$invoice['customer']['curr_code']}} </td>
					<td>
						<a href="{{$inv->template_url($invoice['trans_no'])}}" class="btn btn-outline-primary" target="_blank">Template</a>
						<a href="{{$inv->pdf_url($invoice['trans_no'])}}" class="btn btn-outline-primary" target="_blank">Template PDF</a>
						<a href="{{$inv->view_url($invoice['trans_no'])}}" class="btn btn-outline-primary" target="_blank">Invoice Payload</a>
					</td>
				</tr>
			</tbody>
		</table>
		<a href="{{$inv->xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Generate XML</a>
		<a href="{{$inv->xml_url($invoice['trans_no'], 1)}}" class="btn btn-primary" target="_blank">Generate Signed XML</a>
		<a href="{{$inv->xml_file_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">XML File</a>
		<a href="{{$inv->encode_xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Base64 Encode</a>
		<a href="{{$inv->reporting_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Reporting Invoice</a>
		<a href="{{$inv->compliance_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Check Compliance</a>

		<div class="card bg-light mt-3 mb-2">
			<div class="card-body">
				<h4 class="text-info">QR code</h4>
				@if($qrCode)
					<img src="{!!$qrCode!!}" alt=''>
				@endif
			</div>
		</div>
		<div class="card bg-light">
			<div class="card-body">
				<h4 class="text-info">Reporting Logs</h4>
				<table class="table table-striped table-bordered">
					<thead>
						<tr class="font-weight-bold">
							<td>Action</td>
							<td>Method</td>
							<td>Trans No.</td>
							<td>API Response</td>
							<td>Date</td>
						</tr>
					</thead>
					<tbody>
						@if($logs && $logs->count() > 0)
							@foreach($logs as $log)
							<tr>
								<td>{{$log->action}}</td>
								<td>{{$log->method}}</td>
								<td>{{$log->trans_no}}</td>
								<td><textarea class="form-control" rows="3">{{$log->api_response}}</textarea></td>
								<td>{{Carbon\Carbon::parse($log->created_at)->diffForHumans()}}</td>
							</tr>
							@endforeach
						@else
							<tr>
								<td colspan="5" class="text-center">No records found</td>
							</tr>
						@endif
					</tbody>
				</table>
				{!!$logs->links()!!}
				<small>Total: {{$logs->total()}}</small>
			</div>
		</div>
	@endif
@endsection