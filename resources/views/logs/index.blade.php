@php
	use App\Invoice;
	$title = 'Reporting Logs';
	$actions = [''=>'All actions', 'Compliance'=>'Compliance', 'Reporting'=>'Reporting', 'Clearance'=>'Clearance'];
	$methods = [''=>'All methods', 'api'=>'API', 'app'=>'Application'];
@endphp
@extends('layouts.master')
@section('title') {{$title}} | @endsection
@section('content')
	<div class="row">
		<div class="col-md-3">
			<h3>{{$title}}</h3>
		</div>
		<div class="col-md-9 d-flex justify-content-end">
			{!!$logs->links()!!}
		</div>
	</div>
	<div class="card">
		<div class="card-body">
			<form action="{{route('logs')}}" method="GET" class="form-horizontal" id="filterForm">
				<div class="row">
					<div class="form-group col-md-3">
						<select name="action" class="form-control" onchange="_submit()">
							@foreach($actions as $act=>$lbl)
								<option value="{{$act}}" {{Request::get('action')==$act? 'selected':''}}>{{$lbl}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group col-md-3">
						<select name="method" class="form-control" onchange="_submit()">
							@foreach($methods as $ms=>$lbl)
								<option value="{{$ms}}" {{Request::get('method')==$ms? 'selected':''}}>{{$lbl}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group col-md-4">
						<div class="input-group">
					      <input type="text" value="{{Request::get('q')}}" name="q" placeholder="Search ..." class="form-control" id="tse" aria-describedby="btnse">
						  <div class="input-group-append">
						    <button class="btn btn-outline-primary" type="submit" id="btnse">Search</button>
						  </div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<table class="table table-striped table-bordered">
		<thead>
			<tr class="font-weight-bold">
				<td>Action</td>
				<td>Method</td>
				<td>Trans No.</td>
				<td>API Response</td>
				<td>Date</td>
			</tr>
		</thead>
		<tbody>
			@if($logs && $logs->count() > 0)
				@foreach($logs as $log)
				<tr>
					<td>{{$log->action}}</td>
					<td>{{$log->method}}</td>
					<td>{{$log->trans_no}}</td>
					<td><textarea class="form-control" rows="3">{{$log->api_response}}</textarea></td>
					<td>{{Carbon\Carbon::parse($log->created_at)->diffForHumans()}}</td>
				</tr>
				@endforeach
			@else
				<tr>
					<td colspan="5" class="text-center">No records found</td>
				</tr>
			@endif
		</tbody>
	</table>

	{!!$logs->links()!!}
	<small>Total: {{$logs->total()}}</small>
@endsection
@section('script')
	<script>
		function prettyPrint() {
		    var ugly = document.getElementsByTagName('textarea').value;
		    var obj = JSON.parse(ugly);
		    if(obj) {
			    var pretty = JSON.stringify(obj, undefined, 4);
			    document.getElementsByTagName('textarea').value = pretty;
		    }
		}
		setTimeout(function() {
			
		}, 1000);


		function _submit() {
			document.getElementById('filterForm').submit();
		}
	</script>
@endsection