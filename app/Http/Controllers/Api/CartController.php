<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\User;
use App\Models\Categories;
use App\Models\Products\Products;
use App\Models\DeviceToken;
use App\Models\Rent\Rent;
use App\Models\Notification\Notification;

use App\Services\Payments\PayPalAdaptiveService;
use Illuminate\Support\Facades\Validator;

class CartController extends ApiBaseController
{
    public $successStatus   		= 200;
    public $createdStatus   		= 201;
    public $notFoundStatus  		= 404;
    public $failedStatus    		= 422;
    public $internalServerError    	= 500;

	public function addItemToCart(Request $request)
	{

    	$user = auth()->guard('api')->user();

    	$response = [];
    	$rules = [];
    	$rules['product_id']		= 'required';
    	$rules['rental_start_date'] = 'required|date_format:d-m-Y|after:today';
    	$rules['rental_end_date']	= 'required|date_format:d-m-Y|after:today';
    	$rules['delivery_option']	= 'required|in:Localization,Regular mail,Ups';
		$rules['email'] = 'nullable|email';
    	$rules['street_number']		= 'required';
    	$rules['address']			= 'required';
    	$rules['city']				= 'required';
    	$rules['state']				= 'required';
    	$rules['postal_code'] 		= 'required';
    	$rules['contact_number'] 	= 'required';
    	$rules['country'] 			= 'required';

    	$validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
			return response()->json(
				[
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors',
                'data'      => $validator->errors(),
			], $this->failedStatus);
		}
		
    	$product_details = Products::where('id', $request->product_id)->first();
    	if(!$product_details) {
			return response()->json(
				[
                'status'    => $this->notFoundStatus,
                'message'   => 'Product not found.',
                'data'      => [],
			], $this->notFoundStatus);
    	}

    	$check_already_in_cart = Rent::where('user_id', $user->id)
    	->where('product_id', $request->product_id)
    	->where('status', '=', 'Cart')
		->first();
		
    	if(!$check_already_in_cart) {
    		$product_details = Products::where('id', $request->product_id)->first();

    		$date1 		= date_create($request->rental_start_date);
    		$date2 		= date_create($request->rental_end_date);
    		$diff 		= date_diff($date1, $date2);
    		$total_days = $diff->format("%a");
    		$total_days += 1;

    		if(!isset($request->street_number) || $request->street_number == null) {
    			$request->street_number = '';
    		}
    		if(!isset($request->address) || $request->address == null) {
    			$request->address = '';
    		}
			if(!isset($request->city) || $request->city == null) {
				$request->city = '';
			}
			if(!isset($request->state) || $request->state == null) {
				$request->state = '';
			}
			if(!isset($request->postal_code) || $request->postal_code == null) {
				$request->postal_code = '';
			}
			if(!isset($request->country) || $request->country == null) {
				$request->country = '';
			}
			if(!isset($request->contact_number) || $request->contact_number == null) {
				$request->contact_number = '';
			}
			if(!isset($request->email) || $request->email == null) {
				$request->email = '';
			}
			if(!isset($request->description) || $request->description == null) {
				$request->description = '';
			}
			$add_cart = new Rent;
			$add_cart->user_id				= $user->id;
			$add_cart->product_id			= $request->product_id;
			$add_cart->delivery_option		= $request->delivery_option;
			$add_cart->rental_start_date	= date('m/d/Y', strtotime($request->rental_start_date));
			$add_cart->rental_end_date		= date('m/d/Y', strtotime($request->rental_end_date));
			$add_cart->street_number		= $request->street_number;
			$add_cart->address2				= $request->address;
			$add_cart->city					= $request->city;
			$add_cart->state				= $request->state;
			$add_cart->postal_code			= $request->postal_code;
			$add_cart->country				= $request->country;
			$add_cart->contact_number		= $request->contact_number;
			$add_cart->email = $request->email ? $request->email : $user->email;
			$add_cart->description			= $request->description;
			$add_cart->total				= ($total_days * $product_details->price);
			$add_cart->status				= "Cart";
			$add_cart->save();

			return response()->json(
				[
				'status'    => $this->createdStatus,
				'message'   => 'Product added to cart.',
				'data'      => [],
			], $this->createdStatus);
		}
		return response()->json(
			[
			'status'    => $this->failedStatus,
			'message'   => 'Product already in the cart.',
			'data'      => [],
		], $this->failedStatus);
	}
	
	public function removeItemFromCart(Request $request)
	{

		$user = auth()->guard('api')->user();

		$rules = [];
		$rules['product_id'] = 'required';
		$validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
			return response()->json(
				[
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
		$check_product_in_cart = Rent::where('product_id', $request->product_id)
		->where('user_id', $user->id)
		->where('status', 'Cart')
		->first();

		if($check_product_in_cart) {
			Rent::deleteData($check_product_in_cart->id);

			return response()->json(
				[
				'status'    => $this->successStatus,
				'message'   => 'Product removed from cart.',
				'data'      => [],
			], $this->successStatus);
		}
		return response()->json(
			[
			'status'    => $this->notFoundStatus,
			'message'   => 'Product not found in cart.',
			'data'      => [],
		], $this->notFoundStatus);
	}
	
	public function emptyCart(Request $request)
	{
		$user = auth()->guard('api')->user();
		$check_product_in_cart = Rent::where('user_id', $user->id)->where('status', 'Cart')->first();
		
		if($check_product_in_cart) {
			Rent::emptyData($user->id);
			return response()->json(
				[
				'status'    => $this->successStatus,
				'message'   => 'Your cart has been emptied successfully.',
				'data'      => [],
			], $this->successStatus);
		}
		return response()->json(
			[
			'status'    => $this->notFoundStatus,
			'message'   => 'You have no item in your cart.',
			'data'      => [],
		], $this->notFoundStatus);
	}
	
	public function getCartList(Request $request)
	{

		$user = auth()->guard('api')->user();
		$response = [];

		$this->data['body_type']    =   '';
		$this->data['size']         =   '';
		$this->data['price']        =   Products::max('price');
		$this->data['per']          =   1;
		$this->data['location']     =   '';
		$this->data['height']       =   '';
		$this->data['season']       =   '';
		$this->data['category']     =   '';
		
		if ($request->has('body_type')) {
			$this->data['body_type'] = $request->body_type;
		}
		if ($request->has('size')) {
			$this->data['size'] = $request->size;
		}
		if ($request->has('price')) {
			$this->data['price'] = $request->price;
		}
		if ($request->has('per')) {
			$this->per($request);
		}
		if ($request->has('location')) {
			$this->data['location'] = $request->location;
		}
		if ($request->has('height')) {
			$this->data['height'] = $request->height;
		}
		if ($request->has('season')) {
			$this->data['season'] = $request->season;
		}
		if ($request->has('category')) {
			$this->data['category'] = $request->category;
		}

		$this->data['budget']       =	$this->data['price'] / $this->data['per'];
		$this->data['categories'] 	=	Categories::where('status', 1)->get();

		$cart_list = Rent::groupBy('rent_details.id', 'products.id')
		->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
		->leftjoin('product_categories', 'products.id', '=', 'product_categories.product_id')
		->leftjoin('categories', 'product_categories.category_id', '=', 'categories.id')
		->leftjoin('users', 'products.user_id', '=', 'users.id')
		->where('rent_details.user_id', '=', $user->id )
		->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
		->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
		->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
		->where('products.price', '<=', $this->data['budget'])
			->whereNotIn(
				'rent_details.id', Rent::select('id')->where('user_id', $user->id)->where('status', '!=', 'Cart')
				->get())
		->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
		->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
		->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
			->select(
				'rent_details.id as cartID',
		'products.id as productID')
		->get();

		if(count($cart_list) > 0) {
			$cart_list_id = collect($cart_list)->pluck('cartID')->all();
			
			$cart_list = Rent::whereIn('id', $cart_list_id)->with(
				['product_detail' => function($query) {
					$query->select('id', 'user_id', 'name', 'price', 'picture')->with(
						['added_by' => function($query) {
					$query->select('id', 'first_name', 'last_name');
				}]);
			}])->get();

			foreach ($cart_list as $key => $item) {
				$item['product_detail']['picture'] = env('APP_URL').'/'.$item['product_detail']['picture'];
			}

			return response()->json(
				[
				'status'    => $this->successStatus,
				'message'   => 'Cart list.',
				'data'      => $cart_list,
			], $this->successStatus);
		}
		return response()->json(
			[
			'status'    => $this->failedStatus,
			'message'   => 'Cart is empty.',
			'data'      => [],
		], $this->failedStatus);
	}
	
		public function getPaymentUrl(Request $request)
		{
			$rules = [];
			$rules['rented_id'] = 'required|integer|min:1';
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return response()->json(
					[
					'status'  => $this->failedStatus,
					'message' => 'Validation Errors.',
					'data'    => $validator->errors(),
				], $this->failedStatus);
			}

			return $this->generatePayId($request);
		}

		public function getPaymentStatus(Request $request)
		{
			$rules = [];
			$rules['payment_key'] = 'required|string|max:120';
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return response()->json(
					[
						'status'  => $this->failedStatus,
						'message' => 'Validation Errors.',
						'data'    => $validator->errors(),
					], $this->failedStatus);
			}

			$user = auth()->guard('api')->user();
			$rent = Rent::where('user_id', $user->id)->where('pay_key', $request->payment_key)->first();
			if (!$rent) {
				return response()->json(
					[
						'status'  => $this->notFoundStatus,
						'message' => 'Payment key not found.',
						'data' => [
							'pay_key' => null,
						],
					], $this->notFoundStatus);
			}

			$details = app(PayPalAdaptiveService::class)->getPaymentDetails((string) $request->payment_key, true);
			if ($details['success'] && $details['verified']) {
				return response()->json(
					[
						'status'  => $this->successStatus,
						'message' => $details['message'],
						'data'    => [
							'pay_key' => $request->payment_key,
							'payment_status' => $details['payment_status'],
						],
					], $this->successStatus);
			}
			return response()->json(
				[
					'status'  => $this->failedStatus,
					'message' => $details['message'] ?? 'Payment not yet received',
					'data' => [
						'pay_key' => null,
						'payment_status' => $details['payment_status'] ?? null,
					]
				], $this->failedStatus);
		}
	
	public function placeOrder(Request $request)
	{

		$user = auth()->guard('api')->user();
		
		$this->data['body_type']	=   '';
		$this->data['size'] 		=   '';
		$this->data['budget'] 		=   Products::max('price');
		$this->data['location']     =   '';
		$this->data['height']       =   '';
		$this->data['season']       =   '';
		$this->data['category']     =   '';

		$rent_data = Rent::groupBy('rent_details.id', 'products.id')
		->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
		->leftjoin('product_categories', 'products.id', '=', 'product_categories.product_id')
		->leftjoin('categories', 'product_categories.category_id', '=', 'categories.id')
		->leftjoin('users', 'products.user_id', '=', 'users.id')
		->where('rent_details.user_id', '=', $user->id )
		->where('rent_details.status', 'Cart')
		->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
		->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
		->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
		->where('products.price', '<=', $this->data['budget'])
		->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
		->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
		->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
		->select('rent_details.id as rentID', 'products.id as productID')
		->get();

		if(count($rent_data) > 0) {
			$success = true;
			foreach($rent_data as $value) {
				$product_data 	= Products::where('id', $value->productID)->first();
				$user_data 		= User::where('id', $product_data->user_id)->first();
				if($user_data->paypal_email_address == '') {

					$success = false;
					return response()->json(
						[
						'status'    => $this->failedStatus,
						'message'   => 'Merchant not added paypal account, please contact to admin.',
						'data'      => [],
					], $this->failedStatus);
				}
					else {
                        $verify = app(PayPalAdaptiveService::class)->verifyEmail($user_data->paypal_email_address, true);
                        if (!$verify['verified']) {
                            $success = false;
                            return response()->json(
                                [
                                'status'    => $this->failedStatus,
                                'message'   => 'Paypal account not verified, please verify the account first.',
                                'data'      => [],
                            ], $this->failedStatus);
                        }
					}
				}

			if($success == true) {
				foreach($rent_data as $value) {
					Rent::manageData($value->rentID, 'Pending');

					$product_data = Products::where('id', $value->productID)->with('user_detail')->first();

					$link2 = url('for-rent/booking-list/'.$product_data->id);
					$name = $product_data->user_detail->first_name.' '.$product_data->user_detail->last_name;
					$rent_data = Rent::orderby('id', 'desc')->first();

					Notification::addData(
						$product_data->user_id,
						$user->id,
						$rent_data->id,
						'Rented your item',
						'New product is now pending for approval.',
						'rental_request'
					);

					Notification::sendEmail(
						"Rented your item",
						"New product is now pending for approval.",
						$product_data->user_detail->email,
						$link2,
						$name,
						$user->id,
						$value->rentID,
						$value->productID
					);

					$user_device_token = DeviceToken::where('user_id', $product_data->user_id)->get();

					if(count($user_device_token) > 0) {
						foreach($user_device_token as $key => $value) {
							if($value->device_type == 'Android') {
								$fields = [
									'to' 	=> $value->device_token,
									'data' 	=> [
										'message' => 'New product is now pending for approval.',
										'rental_request',
										'title' => 'Rented your item'
									]
								];
								sendPushNotification($fields);
							}
						}
					}
	            			//Notification::addData($user->id,$product_data->user_id, $rent_data->id, 'One new item rented', 'One new item rented', 'rental_request_sent');
				}
				return response()->json(
					[
					'status'    => $this->successStatus,
					'message'   => 'Order is placed successfully and items are now pending for approval.',
					'data'      => [],
				], $this->successStatus);
			}
		}

		return response()->json(
			[
			'status'    => $this->failedStatus,
			'message'   => 'Cart is empty.',
			'data'      => [],
		], $this->failedStatus);
	}
	
		public function generatePayId(Request $request)
		{
			$user = auth()->guard('api')->user();
			$rules = [];
			$rules['rented_id'] = 'required|integer|min:1';
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return response()->json(
					[
						'status'    => $this->failedStatus,
						'message'   => 'Validation Errors.',
						'data'      => $validator->errors(),
					], $this->failedStatus);
			}

			$response 	= [];
			$rented_id 	= $request->rented_id;
			
			$rented_detail 	= Rent::where('id', $rented_id)->where('user_id', $user->id)->first();
			if ($rented_detail) {
				$product_detail = Products::where('id', $rented_detail->product_id)->first();
			}
		else {
			return response()->json(
				[
				'status'    => $this->notFoundStatus,
				'message'   => 'Rented product detail not found.',
				'data'      => [],
			], $this->notFoundStatus);
		}
			if ($product_detail) {
				$user_detail	= User::where('id', $product_detail ->user_id)->first();
			}
		else {
			return response()->json(
				[
				'status'    => $this->notFoundStatus,
				'message'   => 'Product not found.',
				'data'      => [],
			], $this->notFoundStatus);
		}

				if($rented_detail) {
	                if (empty($user_detail->paypal_email_address)) {
	                    return response()->json(
	                        [
	                        'status'    => $this->failedStatus,
	                        'message'   => 'Merchant has no verified paypal email.',
	                        'data'      => [],
	                    ], $this->failedStatus);
	                }
	                $admin_amount = (float) $rented_detail->total;
	                $merchant_amount = ($admin_amount * 90) / 100;
	                $result = app(PayPalAdaptiveService::class)->createAdaptivePayKey(
                    $admin_amount,
                    $merchant_amount,
                    $user_detail->paypal_email_address,
                    SUCCESS_URL,
                    CANCEL_URL,
                    true
                );
                if (!$result['success'] || !$result['pay_key']) {
                    return response()->json(
                        [
                        'status'    => $this->failedStatus,
                        'message'   => $result['message'],
                        'data'      => [],
	                    ], $this->failedStatus);
	                }
	                $payKey = (string) $result['pay_key'];
	                Rent::where('id', $rented_detail->id)->update(['pay_key' => $payKey]);
	                $response['pay_key'] = $payKey;
	                $response['payment_key'] = $payKey;
	                $response['payment_url'] = app(PayPalAdaptiveService::class)->paymentUrlFromPayKey($payKey);
	                return response()->json(
	                    [
	                    'status'    => $this->successStatus,
	                    'message'   => 'Payment URL has been generated.',
	                    'data'      => $response,
	                ], $this->successStatus);
				}
		return response()->json(
			[
			'status'    => $this->notFoundStatus,
			'message'   => 'Rented product detail not found.',
			'data'      => [],
		], $this->notFoundStatus);
	}
}
