@php
	use App\CreditNote;
	$inv = new CreditNote;
	$inv->setBusiness($business);
@endphp
@extends('layouts.master')
@section('title') Credit Notes | @endsection
@section('content')
	<h2>Credit Notes</h2>
	@if($invoices)
		<table border="1" cellpadding="6" class="table table-striped table-bordered">
			<thead>
				<tr>
					<td>ID</td>
					<td>Ref</td>
					<td>Customer</td>
					<td>Currency</td>
					<td>Total</td>
					<td>Left to allocate</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				@foreach($invoices as $invoice)
				<tr>
					<td><a href="{{$inv->url($invoice['trans_no'])}}">{{$invoice['trans_no']}}</a></td>
					<td>{{$invoice['reference']}}</td>
					<td>{{$invoice['debtor_ref']}}</td>
					<td>@if($invoice['rate'] == 1)
							{{$invoice['curr_code']}}
						@else
							{{$invoice['curr_code']}} {{$invoice['Total']}}
						@endif
					</td>
                    <td>{{ number_format($invoice['Total'] * $invoice['rate']) }}</td>
                    <td>{{ number_format($invoice['Total'] - $invoice['alloc']) }}</td>
					<td>
						<a href="{{$inv->template_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Template</a>
						<a href="{{$inv->view_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">View Invoice</a>
						<a href="{{$inv->xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Generate XML</a>
						<a href="{{$inv->xml_file_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">XML File</a>
						<a href="{{$inv->encode_xml_url($invoice['trans_no'])}}" class="btn btn-primary" target="_blank">Base64 Encode</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	@endif
@endsection