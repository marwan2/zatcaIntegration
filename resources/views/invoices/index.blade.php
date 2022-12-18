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
		<table class="table table-striped table-bordered table-hover">
			<thead>
				<tr class="font-weight-bold">
					<td>ID</td>
					<td>Ref</td>
					<td>Customer</td>
					<td>Total</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				@foreach($invoices as $invoice)
				@php $no = $invoice['trans_no']; @endphp
				<tr>
					<td><a href="{{$inv->url($no)}}">{{$invoice['trans_no']}}</a></td>
					<td>{{$invoice['reference']}}</td>
					<td>{{$invoice['debtor_ref']}}</td>
					<td>{{$invoice['ov_amount']}} {{$invoice['curr_code']}} </td>
					<td>
						<div class="dropdown">
						  <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" id="dmLogs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
						  <div class="dropdown-menu" aria-labelledby="dmLogs">
								<a href="{{$inv->template_url($no)}}" class="dropdown-item" target="_blank">Template</a>
								<a href="{{$inv->pdf_url($no)}}" class="dropdown-item" target="_blank">PDF</a>
								<a href="{{$inv->view_url($no)}}" class="dropdown-item" target="_blank">View Invoice</a>
								<a href="{{$inv->xml_url($no)}}" class="dropdown-item" target="_blank">Generate XML</a>
								<a href="{{$inv->xml_file_url($no)}}" class="dropdown-item" target="_blank">XML File</a>
								<a href="{{$inv->encode_xml_url($no)}}" class="dropdown-item" target="_blank">Base64 Encode</a>
						  </div>
						</div>
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