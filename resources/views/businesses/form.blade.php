@php
	use App\Business;
@endphp
<div class="card">
	<div class="card-body bg-light">
		<div class="row">
			<div class="col-md-6 form-group">
				<label class="col-form-label">Business name</label>
				{!!Form::text('name', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Legal Registration Name</label>
				{!!Form::text('legal_registration_name', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Organization identifier (TRN)</label>
				{!!Form::text('trn', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Organization unit name</label>
				{!!Form::text('organization_unit_name', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Country ISO2</label>
				{!!Form::text('country_iso2', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Location address</label>
				{!!Form::text('location_address', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">OTP</label>
				{!!Form::text('otp', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
		</div>
	</div>
</div>
<div class="card mt-3">
	<div class="card-body bg-light">
		<div class="row">
			<div class="col-md-6 form-group">
				<label class="col-form-label">ERP Prefix</label>
				{!!Form::text('xprefix', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">ERP Auth-Token</label>
				{!!Form::textarea('auth_token', null, ['class'=>'form-control', 'required'=>'', 'rows'=>4])!!}
			</div>
		</div>
	</div>
</div>
<div class="card mt-3">
	<div class="card-body bg-light">
		<div class="row">
			<div class="col-md-6 form-group">
				<label class="col-form-label">Street name</label>
				{!!Form::text('street_name', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Building no</label>
				{!!Form::text('building_no', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">City</label>
				{!!Form::text('city', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">District</label>
				{!!Form::text('district', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Country subentity</label>
				{!!Form::text('country_subentity', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Postal code</label>
				{!!Form::text('postal_code', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
		</div>
	</div>
</div>
<div class="card mt-3">
	<div class="card-body bg-light">
		<div class="row">
			<div class="col-md-6 form-group">
				<label class="col-form-label">Identification scheme</label>
				{!!Form::select('identification_scheme', Business::$scheme, null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
			<div class="col-md-6 form-group">
				<label class="col-form-label">Identification ID</label>
				{!!Form::text('identification_id', null, ['class'=>'form-control', 'required'=>''])!!}
			</div>
		</div>
	</div>
</div>
<div class="form-group text-center mt-3 mb-4 pb-5">
	<button type="submit" class="btn btn-primary btn-lg">Submit</button>
	<a href="{{url('businesses')}}" class="btn btn-light btn-lg">Cancel</a>
</div>