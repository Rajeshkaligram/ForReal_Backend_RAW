<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="{{ config('app.locale') }}"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang="{{ config('app.locale') }}"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="{{ config('app.locale') }}"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="{{ config('app.locale') }}">
    <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>@yield('title')</title>
        <meta name="description" content=""/>
        <meta name="keywords" content=""/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('user-interface.includes.css')
    </head>
    <body>
	    <div class="container-fluid" style="margin-top: 15px !important;">
	        @yield('content')
        </div>
    </body>
</html>