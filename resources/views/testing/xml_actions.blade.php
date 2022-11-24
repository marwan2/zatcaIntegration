@php
	
@endphp
@extends('layouts.master')
@section('content')
	<div class="container">
		<div class="card">
			<div class="card-header">
				<h3>Test generating XML</h3> 
				<small>Using one of the following actions:</small>
			</div>
			<div class="card-body">
				<form class="form-horizontal" method="POST" action="{{route('testing.xml')}}" class="mt-4">
					@csrf
					<div class="form-group">
						<label>Select Business</label>
						<select name="business_id" class="form-control" required>
							<option value="">Select Business</option>
							@foreach($businesses as $bs)
								<option value="{{$bs->id}}">{{$bs->name}}</option>>
							@endforeach
						</select>
					</div>
					<div class="form-group">
						<label>XML File</label>
						<div class="input-group input-group-lg">
						  <div class="input-group-prepend">
						    <span class="input-group-text" id="xmfile">{{url('xmls')}}/</span>
						  </div>
						  <input type="text" name="filename" class="form-control" placeholder="XML file name" aria-label="xml_file" aria-describedby="xmfile" required>
						</div>
					</div>
					<div class="form-group">
						<label class="font-weight-bold">Action</label>
						<select name="action" class="form-control" required>
							<option value="">Select Action</option>
							@foreach($actions as $act=>$label)
								<option value="{{$act}}">{{$label}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group">
						<button class="btn btn-primary" type="submit">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection