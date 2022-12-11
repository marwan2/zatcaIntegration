@php
	use App\Business;
@endphp
@extends('layouts.master')
@section('title') Business - @endsection
@section('content')
	<h3 class="font-weight-bold">Business: {{$business->name}}</h3>
	<div class="card mb-3 bg-light">
		<div class="card-body">
			<div class="row">
				<div class="col-md-3">
					<label class="font-weight-bold">ISO Code</label>
					<div>{{$business->country_iso2}}</div>
				</div>
				<div class="col-md-3">
					<label class="font-weight-bold">Location Address</label>
					<div>{{$business->location_address}}</div>
				</div>
				<div class="col-md-3">
					<label class="font-weight-bold">TRN</label>
					<div>{{$business->trn}}</div>
				</div>
				<div class="col-md-3">
					<label class="font-weight-bold">ERP Prefix</label>
					<div>{{$business->xprefix}}</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card mb-3 bg-light">
		<div class="card-body">
			<div class="row">
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">Street name</label>
					<div>{{$business->street_name}}</div>
				</div>
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">Building no</label>
					<div>{{$business->building_no}}</div>
				</div>
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">City</label>
					<div>{{$business->city}}</div>
				</div>
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">District</label>
					<div>{{$business->district}}</div>
				</div>
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">Country subentity</label>
					<div>{{$business->country_subentity}}</div>
				</div>
				<div class="col-md-2 form-group">
					<label class="font-weight-bold">Postal code</label>
					<div>{{$business->postal_code}}</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card mb-3 bg-light">
		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					<label class="font-weight-bold">Compliance Certificate</label>
					<textarea class="form-control" rows="12" disabled>{!!json_encode($business->getCCSID())!!}</textarea>
				</div>
				<div class="col-md-6">
					<label class="font-weight-bold">Production Certificate</label>
					<textarea class="form-control" rows="12" disabled>{!! json_encode($business->getPCSID())!!}</textarea>
				</div>
			</div>
			<div class="row">
				@php
					$cert = $business->xprefix.'_compliance_cert.pem';
					$cert_p = $business->xprefix.'_production_cert.pem';
				@endphp
				<div class="col-md-6 mt-3">
					<label class="font-weight-bold">Compliance .pem file</label>
					@if(Storage::exists($cert))
						<a href="{{url('cert-download/'.$cert)}}" target="_blank">{{$cert}}</a>
					@else
						<div class="badge badge-danger">Not found</div>
					@endif
				</div>
				<div class="col-md-6 mt-3">
					<label class="font-weight-bold">Production .pem file</label>
					@if(Storage::exists($cert_p))
						<a href="{{url('cert-download/'.$cert_p)}}" target="_blank">{{$cert_p}}</a>
					@else
						<div class="badge badge-danger">Not found</div>
					@endif
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					ERP Business onboarded: 
					@if($business->erp_onboarding_status)
						<div class="badge badge-success">Yes</div>
					@else
						<div class="badge badge-danger">No</div>
					@endif
				</div>
				<div class="card-body">
					<form action="{{url('businesses/'.$business->id.'/update-erp-onboarding-status')}}" method="POST"> @csrf
						<div class="form-group form-inline">
							<select name="is_onboarded" class="form-control" required>
								<option value="">Select</option>
								<option value="1">Onboarded</option>
								<option value="0">Not Onboarded</option>
							</select>
							<button type="submit" class="btn btn-primary" onclick="return window.confirm('Change onboarding status: Are you sure?')">Change status</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="mt-3 card bg-light">
		<div class="card-body">
			<a href="{{route('businesses.edit', $business->id)}}" class="btn btn-outline-warning">Edit Business</a>
			<a href="{{route('cert.pem', $business->id)}}" class="btn btn-outline-info" onclick="return window.confirm('Re-generate Certificates in .pem format: Are you sure?')">Generate .pem Certificates</a>
			<a href="{{route('onboarding', $business->id)}}" class="btn btn-outline-success" onclick="return window.confirm('Becareful this will overwrite current CSR, CCSID, PCSID: Are you sure?')">Re-run Onboarding Process</a>
		</div>
	</div>
@endsection