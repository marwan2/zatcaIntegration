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
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr class="font-weight-bold">
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
				@php $no = $invoice['trans_no']; @endphp
				<tr>
					<td><a href="{{$inv->url($no)}}">{{$invoice['trans_no']}}</a></td>
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
						<div class="dropdown">
						  <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" id="dmLogs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
						  <div class="dropdown-menu" aria-labelledby="dmLogs">
							<a href="{{$inv->template_url($no)}}" class="dropdown-item" target="_blank">Template</a>
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
	@endif
@endsection