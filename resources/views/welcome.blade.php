<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>McLedger Zatca</title>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">
        <style>
            html,body{background-color:#fff;color:#636b6f;font-family:'Nunito',sans-serif;font-weight:200;height:100vh;margin:0}
            .full-height{height:100vh}
            .flex-center{align-items:center;display:flex;justify-content:center}
            .position-ref{position:relative}
            .top-right{position:absolute;right:10px;top:18px}
            .content{text-align:center}
            .title{font-size:84px}
            .links > a{color:#636b6f;padding:0 25px;font-size:15px;font-weight:600;letter-spacing:.1rem;text-decoration:none;text-transform:uppercase}
            .m-b-md{margin-bottom:30px}
        </style>
    </head>
    <body>
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
                <div class="title m-b-md">
                    McLedger Zatca Integration
                </div>

                <div class="links">
                    <a href="{{url('simple-invoice')}}">Generate Simple Invoice XML 2.1</a>
                    <a href="{{url('simple-invoice2')}}">Generate Simple Invoice XML 2.2</a>
                    <a href="{{url('standard-invoice')}}">Generate Standard Invoice XML</a>
                </div>

                <div class="form" style="margin-top: 20px; border: 5px solid #f0f0f0; padding:20px;">
                    <form action="{{url('encode-file')}}" method="GET">
                        <input type="text" name="filename" class="form-control" placeholder="XML file name">
                        <button type="submit">Encode File</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
