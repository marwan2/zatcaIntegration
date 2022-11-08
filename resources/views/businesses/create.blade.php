@php
	use App\Business;
@endphp
@extends('layouts.master')

@section('content')
<h3 class="font-weight-bold">Add new Business</h3>
<form class="form-horizontal" method="POST" action="{{route('businesses.store')}}">
	@csrf
	@include('businesses.form')
</form>
@endsection