@php
	use App\Business;
@endphp
@extends('layouts.master')
@section('title') Businesses - @endsection
@section('content')
	<h3>Businesses</h3>
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<td>ID</td>
				<td>Name</td>
				<td>TRN</td>
				<td>Country</td>
				<td>ERP Prefix</td>
				<td>Date</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
			@if($businesses)
				@foreach($businesses as $bs)
				<tr>
					<td>{{$bs->id}}</td>
					<td><a href="{{url('businesses/'.$bs->id)}}" class="btn">{{$bs->name}}</a></td>
					<td>{{$bs->trn}}</td>
					<td>{{$bs->xprefix}}</td>
					<td>{{$bs->country_code}}</td>
					<td>{{$bs->created_at}} </td>
					<td nowrap="">
						<a href="{{url('businesses/'.$bs->id.'/edit')}}" class="btn btn-warning">Edit</a>
						<a href="{{url('invoices?business_id='.$bs->id)}}" class="btn btn-primary">Invoices</a>
						<a href="{{route('csid.renewal', $bs->id)}}" class="btn btn-primary">PCSID Renewal</a>
					</td>
				</tr>
				@endforeach
			@endif
		</tbody>
	</table>
		
	{!! $businesses->links() !!}
@endsection