@php
    use App\Helper;
    $title = 'Change my password';
@endphp
@extends('layouts.master')
@section('title') {{$title}} @endsection
@section('content')
<div class="row">
	<div class="col-12">
		<div class="dashboard-headline">
			<h3>{{$title}}</h3>
		</div>
      @if(Session::has('flash_message'))
          <div class="alert alert-{{Session::get('alert','success')}}" style="margin-bottom: 0">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {!!Session::get('flash_message')!!}
          </div>
      @endif
	</div>
	<div class="col-12 col-md-12 chpass_wrap">
		<div class="card card-default">
			<div class="card-body">
				<div class="col-12">
					{!!Form::open(['class'=>'form-horizontal', 'url'=>'account/password'])!!}
						<div class="form-group {{ $errors->has('old_password') ? 'has-error' : ''}}">
							{!!Form::label('old_password', 'Current password', ['class'=>'control-label'])!!}
							{!!Form::password('old_password', ['class'=>'form-control with-border', 'required'=>'required'])!!}
							{!! $errors->first('old_password', '<p class="help-block">:message</p>') !!}
						</div>
						<div class="form-group {{ $errors->has('password') ? 'has-error' : ''}}">
							{!!Form::label('password', 'New password', ['class'=>'control-label'])!!}
							{!!Form::password('password', ['class'=>'form-control with-border', 'required'=>'required'])!!}
							{!! $errors->first('password', '<p class="help-block">:message</p>') !!}
						</div>
						<div class="form-group {{ $errors->has('password_confirmation') ? 'has-error' : ''}}">
							{!!Form::label('password_confirmation', 'Confirm new password', ['class'=>'control-label'])!!}
							{!!Form::password('password_confirmation', ['class'=>'form-control with-border', 'required'=>'required'])!!}
							{!! $errors->first('password_confirmation', '<p class="help-block">:message</p>') !!}
						</div>
						<div class="form-group">
							{!!Form::submit('Confirm', ['class'=>'btn btn-dark btn_chpass'])!!}
							<a href="{{url('account')}}" class="btn btn-outline-secondary">Cancel</a>
						</div>
					{!!Form::close()!!}
				</div>
			</div>
		</div>
	</div>
</div>
@endsection