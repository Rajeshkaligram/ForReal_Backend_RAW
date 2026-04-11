@extends('api.layout')
@section('title' , '')
@section('css')
	{{--<link href="{!!asset('user-interface')!!}/css/owl.carousel.min.css" rel="stylesheet">--}}
@stop

@section('content')
	
	@if (isset($data['message']) && isset($data['data']['items']))
		<div class="alert alert-success">
			{{ $data['message'] }}
		</div>
		
		<h3>RENTED ITEM DETAILS</h3>
		<small>
			Please see following item details:
		</small>
		@foreach($data['data']['items'] as $item)
			<hr>
			<div class="row">
				<div class="col-xs-12 col-sm-4">
					<div class="img-holder">
						<img src="{!!asset($item->product_detail->picture)!!}" style="max-width: 200px; margin-bottom: 25px">
					</div>
				</div>
				<div class="col-xs-12 col-sm-8">
					<div class="rent-details-holder">
						
						<div class="row list-container">
							<strong class="col-md-4">PRODUCT</strong>
							<div class="col-md-8">
								<p>{{$item->product_detail->name}}</p>
							</div>
						</div>
						<div class="row list-container">
							<strong class="col-md-4">DELIVERY OPTION</strong>
							<div class="col-md-8">
								<p>{{$item->delivery_option}}</p>
							</div>
						</div>
						<div class="row list-container">
							<strong class="col-md-4">RENTAL PERIOD</strong>
							<div class="col-md-8">
								<p>From: {{$item->rental_start_date}}</p>
								<p>To: {{$item->rental_end_date}}</p>
							</div>
						</div>
						<div class="row list-container">
							<strong class="col-md-4">DELIVERY LOCATION</strong>
							<div class="col-md-8">
								<p>{{$item->street_number}} {{$item->route}}, {{$item->city}} {{$item->state}}
									, {{$item->postal_code}}, {{$item->country}}</p>
								<p>- {{$item->address2}}</p>
								<p>- {{$item->address3}}</p>
							</div>
						</div>
						<div class="row list-container">
							<strong class="col-md-4">OTHER DETAIS</strong>
							<div class="col-md-8">
								<p>Email: {{$item->email}}</p>
								<p>Contact#: {{$item->contact_number}}</p>
								<p>Description: {{$item->description}}</p>
							</div>
						</div>
						<div class="row list-container">
							<strong class="col-md-4">STATUS</strong>
							<div class="col-md-8">
								<p>
									<strong>
										{{$item->status}}
									</strong>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		@endforeach
	@else
		<div class="alert alert-danger">
			Invalid request
		</div>
	@endif
	<div style="margin: 50px"></div>
	
@endsection