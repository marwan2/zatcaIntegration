@php
	use App\Business;
@endphp
@extends('layouts.master')
@section('title') Edit Business - @endsection
@section('content')
	<h3 class="font-weight-bold">Edit Business: {{$business->name}}</h3>

	{!! Form::model($business, ['method'=>'PATCH', 'url'=>['businesses', $business->id], 'class'=>'form-horizontal'])!!}
	@include ('businesses.form')
	{!! Form::close() !!}
@endsection