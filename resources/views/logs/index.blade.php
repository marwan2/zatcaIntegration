@php
	use App\Invoice;
	$title = 'Reporting Logs';
	$actions = [''=>'All actions', 'Compliance'=>'Compliance', 'Reporting'=>'Reporting', 'Clearance'=>'Clearance'];
	$methods = [''=>'All methods', 'api'=>'API', 'app'=>'Application'];
	$types = [''=>'All Trans Types', 'invoice'=>'Sales invoice', 'credit_note'=>'Credit Note'];
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
			<form action="{{route('logs.index')}}" method="GET" class="form-horizontal" id="filterForm">
				<div class="row">
					<div class="col-md-3">
						<select name="action" class="form-control" onchange="_submit()">
							@foreach($actions as $act=>$lbl)
								<option value="{{$act}}" {{Request::get('action')==$act? 'selected':''}}>{{$lbl}}</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-3">
						<select name="method" class="form-control" onchange="_submit()">
							@foreach($methods as $ms=>$lbl)
								<option value="{{$ms}}" {{Request::get('method')==$ms? 'selected':''}}>{{$lbl}}</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-3">
						<select name="type" class="form-control" onchange="_submit()">
							@foreach($types as $type=>$lbl)
								<option value="{{$type}}" {{Request::get('type')==$type? 'selected':''}}>{{$lbl}}</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-3">
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
					<td>
						<a href="javascipt:void(0)" class="prettify" onclick="prettyPrint('txt{{$log->id}}')">Format</a> 
						@php
							$json = str_replace('\\', '', $log->api_response);
							$json = trim($json, '"');
							$json_string = json_encode(json_decode($json), JSON_PRETTY_PRINT);
						@endphp
						<textarea class="form-control" id="txt{{$log->id}}" rows="3">{{$json}}</textarea>
					</td>
					<td>{{Carbon\Carbon::parse($log->created_at)->diffForHumans()}}</td>
					<td>
						<div class="dropdown">
						  <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" id="dmLogs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
						  <div class="dropdown-menu" aria-labelledby="dmLogs">
						    {!!App\Helper::delete_ctrl($log, 'logs', 'dropdown-item')!!}
						  </div>
						</div>
					</td>
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
		function prettyPrint(textarea_o) {
		    var ugly = document.getElementById(textarea_o).value;
		    var obj = JSON.parse(ugly);
		    if(obj) {
			    var pretty = JSON.stringify(obj, undefined, 4);
			    document.getElementById(textarea_o).value = pretty;
		    }
		}


		function _submit() {
			document.getElementById('filterForm').submit();
		}
	</script>
@endsection