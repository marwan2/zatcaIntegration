<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title') McLedger Zatca</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" type="text/css" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-success bg-dark mb-3">
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
	    </ul>
	    <form class="form-inline my-2 my-lg-0">
	      <a href="{{url('businesses/create')}}" class="btn btn-success my-2 my-sm-0">New Integration</a>
	    </form>
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
		@yield('content')
	</main>
	<footer>
		
	</footer>
</body>
</html>