@extends('layouts.master')
@section('content')
    <div class="flex-center position-ref full-height">
        @if (Route::has('login'))
            <div class="top-right links">
                @auth
                    <a href="{{ url('/home') }}">Home</a>
                @else
                    <a href="{{ route('login') }}">Login</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            </div>
        @endif

        <div class="content">
            <h2 class="title m-b-md">
                McLedger Zatca Integration
            </h2>

            <div class="links">
                <a href="{{url('invoices')}}" class="btn btn-primary">Invoices</a>
            </div>

            {{-- <div class="form" style="margin-top: 20px; border: 5px solid #f0f0f0; padding:20px;">
                <form action="{{url('encode-file')}}" method="GET">
                    <input type="text" name="filename" class="form-control" placeholder="XML file name">
                    <button type="submit">Encode File</button>
                </form>
            </div> --}}
        </div>
    </div>
@endsection