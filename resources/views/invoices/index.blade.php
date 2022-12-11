@php
	use App\Invoice;
	$inv = new Invoice;
	$inv->setBusiness($business);
@endphp
@extends('layouts.master')
@section('title') Invoices | @endsection
@section('content')
	<h2>Invoices</h2>

	@if($invoices)
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<td>ID</td>
					<td>Ref</td>
					<td>Customer</td>
					<td>Total</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				@foreach($invoices as $invoice)
				<tr>
					<td><a href="{{$inv->url($invoice['trans_no'])}}">{{$invoice['trans_no']}}</a></td>
					<td>{{$invoice['reference']}}</td>
					<td>{{$invoice['debtor_ref']}}</td>
					<td>{{$invoice['ov_amount']}} {{$invoice['curr_code']}} </td>
					<td>
						<a href="{{$inv->template_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Template</a>
						<a href="{{$inv->pdf_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">PDF</a>
						<a href="{{$inv->view_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">View Invoice</a>
						<a href="{{$inv->xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Generate XML</a>
						<a href="{{$inv->xml_file_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">XML File</a>
						<a href="{{$inv->encode_xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Base64 Encode</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>

		@if(isset($data['next_url']) && !empty($data['next_url']))
			<a href="{{url('invoices?page='.$data['next_url'])}}" class="btn btn-outline-primary">Next &gt; </a>
		@endif
	@else
		<div class="alert alert-danger">No invoices found</div>
	@endif
@endsection