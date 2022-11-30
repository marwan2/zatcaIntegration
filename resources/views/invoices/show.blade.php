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

		<br><br>
		<h3>QR code</h3>
		@if($qrCode)
			<img src="{!!$qrCode!!}" alt=''>
		@endif
	@endif
@endsection