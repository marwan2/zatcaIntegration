@php
	use App\Invoice;
	$inv = new Invoice;
	$inv->setBusiness($business);
@endphp
@extends('layout')

@section('content')
<style>
	.btn { padding: 4px 6px; color: #fff; background: #0e2daa; font-family: sans-serif; text-decoration: none; }
	.btn:hover { background: #ff00aa; color: #fff; }
	.btn:active { background: #fa0a00; color: #fff; }
</style>
	@if($businesses)
		<ul>
		@foreach($businesses as $bs)
			<li><a href="{{url('invoices?business_id='.$bs->id)}}">{{$bs->name}}</a></li>
		@endforeach
		</ul>
	@endif
	<hr>
	@if($invoices)
		<table border="1" cellpadding="6" style="border-collapse: collapse;">
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
					<td>{{$invoice['trans_no']}}</td>
					<td>{{$invoice['reference']}}</td>
					<td>{{$invoice['debtor_ref']}}</td>
					<td>{{$invoice['ov_amount']}} {{$invoice['curr_code']}} </td>
					<td>
						<a href="{{$inv->template_url($invoice['trans_no'])}}" class="btn" target="_blank">Template</a>
						<a href="{{$inv->pdf_url($invoice['trans_no'])}}" class="btn" target="_blank">PDF</a>
						<a href="{{$inv->view_url($invoice['trans_no'])}}" class="btn" target="_blank">View Invoice</a>
						<a href="{{$inv->xml_url($invoice['trans_no'])}}" class="btn" target="_blank">Generate XML</a>
						<a href="{{$inv->xml_file_url($invoice['trans_no'])}}" class="btn" target="_blank">XML File</a>
						<a href="{{$inv->encode_xml_url($invoice['trans_no'])}}" class="btn" target="_blank">Base64 Encode</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	@endif
@endsection