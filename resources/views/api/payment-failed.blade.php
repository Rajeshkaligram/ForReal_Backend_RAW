@extends('api.layout')
@section('title' , '')
@section('css')
	{{--<link href="{!!asset('user-interface')!!}/css/owl.carousel.min.css" rel="stylesheet">--}}
@stop

@section('content')
	
	@if (isset($data['message']))
		<div class="alert alert-danger">
			{{ $data['message'] }}
		</div>
	@else
		<div class="alert alert-danger">
			Invalid request
		</div>
	@endif
	<div style="margin: 50px"></div>
	
@endsection