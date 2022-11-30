@php
	use App\Business;
@endphp
@extends('layouts.master')
@section('title') Businesses - @endsection
@section('content')
	<h3>Switch Business</h3>
	
	@if($businesses)
		@foreach($businesses as $bs)
			<a href="{{url('businesses/switch/'.$bs->id)}}" class="btn btn-outline-info">{{$bs->name}} <br>
				Prefix: {{$bs->xprefix}}
			</a>
		@endforeach
	@endif
@endsection