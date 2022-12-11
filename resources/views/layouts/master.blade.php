<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title') McLedger Zatca Intgeration</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" type="text/css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <a class="navbar-brand" href="{{url('/')}}">McLedger Zatca Integration</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nvb" aria-controls="nvb" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="nvb">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="{{url('/')}}">Home</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{url('businesses')}}">Businesses</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{url('invoices')}}">Invoices</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{url('credit-notes')}}">Credit Notes</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{url('xml/tests')}}">XML Testing</a>
      </li>
    </ul>
    <form class="form-inline my-2 my-lg-0">
    </form>
    <ul class="navbar-nav ml-auto">
        @guest
            <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">@lang('Login')</a></li>
            @if (Route::has('register'))
                <li class="nav-item"><a class="nav-link" href="{{route('register')}}">@lang('Register')</a></li>
            @endif
        @else
      		<li><a href="{{url('businesses/create')}}" class="btn btn-outline-success">New Business Integration</a></li>
            <li><a class="btn" href="{{url('account')}}">{{ Auth::user()->name }}</a></li>
            <li><a class="btn btn-outline-info" href="{{route('logout')}}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a></li>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        @endguest
    </ul>
  </div>
</nav>
<main class="container">
	@if ($errors->any())
        <ul class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
	@if(Session::has('flash_message'))
	    <div class="mb-2 mt-2 alert alert-{{Session::get('alert','success')}}">
	      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
	      {!!Session::get('flash_message')!!}
	    </div>
	@endif
	@if(Session::has('se_business') && Session::get('se_business'))
	    <div class="alert alert-primary bg-dark text-white d-flex justify-content-between">
	      <strong>Business: 
	      {!!Session::get('se_business')->name ?? ''!!} 
	      (Prefix: {!!Session::get('se_business')->xprefix ?? ''!!})
	      </strong>
	      <a href="{{url('businesses/select')}}" class="btn btn-outline-light">Switch</a>
	    </div>
	@endif

	@yield('content')
</main>
<footer class="w-100 bg-light mt-5 p-2 text-center text-muted">
	<small>By McLedger</small>
</footer>
@yield('script')
</body></html>