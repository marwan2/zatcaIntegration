@extends('layouts.master')
@section('content')
	
	<div class="container">
		@if(session()->has('token') && session()->get('token'))
			<div class="alert alert-light border-dark">
				Your API token is: <strong>{{session()->get('token')}}</strong>
				<br>It will be displayed one time only.
			</div>
		@endif

		<h4 class="mb-3">Welcome, {{auth()->user()->name}}</h4>
		<div class="row">
			<div class="col-md-6">
				<strong>To Refresh API Token click here</strong><br>
				<a href="{{url('account/refresh-token')}}" class="btn btn-outline-info">Refresh token</a>
			</div>
		</div>
	</div>
@endsection