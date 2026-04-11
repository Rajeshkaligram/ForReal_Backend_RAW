<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Notifications\RegistrationVerificationCodeSend;
use App\User;
use App\Models\Categories;
use App\Models\Products\Products;
use App\Models\Products\ProductCategories;
use App\Models\Products\ProductPhotos;
use App\Models\DeviceToken;
use App\Models\Wishlist\Wishlist;
use App\Models\ProductUserReview;
use App\Models\Rent\Rent;
use App\Models\Messages\Messages;
use App\Models\Messages\MessagesRoom;
use App\Models\Notification\Notification;
use App\Models\Validators;
use App\Models\Helper;
use App\Models\Dropzone;
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;

class PostItemController extends ApiBaseController {

	public $successStatus   = 200;
	public $createdStatus   = 201;
	public $notFoundStatus  = 404;
	public $failedStatus    = 422;

	public function addProduct(Request $request) {

        $rules = [];
        $rules['name'] 			= 'required|string|min:3';
        $rules['picture'] 		= 'required|string';
        $rules['designer'] 		= 'required|string';
        $rules['price'] 		= 'required|integer|min:1';
        $rules['retail_price'] 	= 'required|integer|min:1';
        $rules['color'] 		= 'required|string';
        $rules['size'] 			= 'required|string';
        $rules['season'] 		= 'required|string';
        $rules['description'] 	= 'required|string';
        $rules['cancellation'] 	= 'required|string';
        $rules['alteration'] 	= 'required|string';
        $rules['condition'] 	= 'required|string';
        $rules['category_id'] 	= 'required|integer|between:1,11';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

		$user = auth()->guard('api')->user();

		// if($user->paypal_email_address == '' || $user->verify_paypal_email == 0) {
		// 	return response()->json([
		// 		'status'    => $this->failedStatus,
		// 		'message'   => 'Paypal account not verified, please verify the account first.',
		// 		'data'      => [],
		// 	], $this->failedStatus);
		// }
		$product_data = Products::manageData64($request, $user->id);
		ProductCategories::addDataApi($product_data->id, $request->category_id);

		if ($product_data) {
			// if($request->sub_photos != '') {
			// 	ProductPhotos::addData($product_data->id, $user->id, 0, $request);
			// } else {
			// 	Dropzone::where('ip', $user->id)->delete();
			// }
			return response()->json([
				'status'    => $this->createdStatus,
				'message'   => 'New product added successfully.',
				'data'      => [],
			], $this->createdStatus);
		}

		return response()->json([
			'status'    => $this->failedStatus,
			'message'   => 'Product could not be added.',
			'data'      => [],
		], $this->failedStatus);
	}
	
	public function editProduct(Request $request) {
		
		$user = auth()->guard('api')->user();
		$response = [];

		// if($user->paypal_email_address=="" || $user->verify_paypal_email==0) {
		// 	$msg = "Paypal Account not verify. please go to profile and update your paypal account.";
		// 	return Response::json(array('code' => $code,'msg' => $msg,'data' => (object)$response));
		// }
		
		$product = Products::manageData($request, $user->id);
		if ($product) {
			ProductCategories::addDataApi($product->id, $request->category_id);

			$product['picture'] = env('APP_URL').'/'.$product['picture'];

			$response['product_detail'] = $product;

			return response()->json([
				'status'    => $this->successStatus,
				'message'   => 'Product has been updated.',
				'data'      => $response,
			], $this->successStatus);
		}
		
		// if($request->sub_photos != '') {
		// 	ProductPhotos::addData($product->id, $user->id, 0, $request);
		// } else {
		// 	Dropzone::where('ip', $user->id)->delete();
		// }

		return response()->json([
			'status'    => $this->failedStatus,
			'message'   => 'Product not updated.',
			'data'      => [],
		], $this->failedStatus);
	}
	
	public function uploadProductPhoto(Request $request) {
        $rules = [];
        $rules['file'] = 'required|string';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

		$user = auth()->guard('api')->user();
		$response = [];
		$request->label = "items";
		$request->ip 	= $user->id;

		$data_added = Dropzone::addData64($request);
		if (!$data_added) {
	        return response()->json([
	            'status'    => $this->failedStatus,
	            'message'   => 'Could not add photo.',
	            'data'      => [],
	        ], $this->failedStatus);
		}
		$data_added['file'] = env('APP_URL').$data_added['file'];
		$response['photo_detail'] = $data_added;

        return response()->json([
            'status'    => $this->createdStatus,
            'message'   => 'Photo uploaded.',
            'data'      => $response,
        ], $this->createdStatus);
	}
	
	public function removeProductPhoto(Request $request) {

		$user = auth()->guard('api')->user();
		$sub_photos_detail = Dropzone::where('id', $request->id)->where('ip', $user->id)->first();
		
		if($sub_photos_detail) {
			$sub_photos_detail->delete();
	        return response()->json([
	            'status'    => $this->successStatus,
	            'message'   => 'Product photo removed.',
	            'data'      => [],
	        ], $this->successStatus);
		}
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product photo not found.',
            'data'      => [],
        ], $this->notFoundStatus);
	}
	
	public function getEditPostItemDetail($product_id) {

		$user = auth()->guard('api')->user();
		$response = [];
		$product_data = Products::where('id', $product_id)->where('user_id', $user->id)->first();
		
		if ($product_data) {
			$this->data['label'] 		= 'Edit';
			$this->data['product_data'] = Products::getData($product_data->id);
			// $sub_photos = [];
			// if (count($this->data['product_data']->sub_photo)) {
			// 	foreach ($this->data['product_data']->sub_photo as $value) {
		 //            // Dropzone holds -> ip, label_name, file, and size
			// 		$id = Dropzone::addDataCustom($user->id, 'items', $value->sub_photo, $value->size);
			// 		$sub_photos[] =  $id;
			// 	}
			// }
			$product_detail = [];

			$size = [];
			$size[0]['display'] = "Extra Small";
			$size[1]['display'] = "Small";
			$size[2]['display'] = "Medium";
			$size[3]['display'] = "Large";
			$size[4]['display'] = "Extra Large";
			
			$size[0]['value'] = "0";
			$size[1]['value'] = "1";
			$size[2]['value'] = "2";
			$size[3]['value'] = "3";
			$size[4]['value'] = "4";

			$display_size = "Extra Small";

			foreach($size as $key => $value) {
				if($this->data['product_data']->self_data->size == $value['display']) {
					$display_size = $value['display'];
				}
			}

			$this->data['product_data']->self_data->display_size = $display_size;
			// $sub_photo 	= Dropzone::whereIn('id', $sub_photos)->get();
			// $sub_photos = implode(',', $sub_photos);
			// $this->data['product_data']->self_data->sub_photos_id 	= $sub_photos;
			// $this->data['product_data']->self_data->sub_photos 		= $sub_photo;

			$product_detail['product_detail'] = $this->data['product_data']->self_data;
			$product_detail['product_detail']['categories'] = $this->data['product_data']->categories[0];

			$response = $product_detail;
			$response['product_detail']['picture'] = env('APP_URL').'/'.$response['product_detail']['picture'];
			$response['product_detail']['categories']->picture = env('APP_URL').'/'.$response['product_detail']['categories']->picture;
			// foreach ($response['product_detail']['sub_photos'] as $key => $value) {
			// 	$value->file = env('APP_URL').'/'.$response['product_detail']['sub_photos'][$key]['file'];
			// }

	        return response()->json([
	            'status'    => $this->successStatus,
	            'message'   => 'Product found.',
	            'data'      => $response,
	        ], $this->successStatus);
		} 
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found.',
            'data'      => [],
        ], $this->notFoundStatus);
	}
	
	public function getMyAddedProducts(Request $request) {

			$user = auth()->guard('api')->user();
			$response = [];
			$total 	= (int) $request->results_per_page;
			if ($total < 1) { $total = 10; }
			$page 	= max(1, (int) $request->page);
			$skip 	= ($page - 1) * $total;
			$this->data['sort_index'] = 'created_at';
		$this->data['sort_value'] = 'desc';
		if ($request->sort) {
			$this->sort($request);
		}
		$products = Products::where('user_id', $user->id)
		->orderBy($this->data['sort_index'], $this->data['sort_value']);

        $all_products = $products->get()->toArray();

		$products = $products->skip($skip)->take($total)->get(['id', 'name', 'price', 'picture', 'designer']);

		if(count($products ) > 0) {
			foreach($products as $key => $value) {
				$value->picture = env('APP_URL').'/'.$value->picture;

				$rating = ProductUserReview::where('product_id', $value['id'])->avg('rating');                		   	          
				$rating = round($rating);
				$value->rating = $rating;
			}

			$response['my_products'] 	= $products;
            $response['total']          = count($all_products);
            $response['showing']        = count($products);
            $response['current_time']   = date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'My added products.',
                'data'      => $response,
            ], $this->successStatus);
		}
            return response()->json([
                'status'    => $this->notFoundStatus,
                'message'   => 'Products not found.',
                'data'      => [],
            ], $this->notFoundStatus);
	}

	public function sort($request) {
		switch ($request->sort) {
			case 'date-recently':
			$this->data['sort_index'] = 'created_at';
			$this->data['sort_value'] = 'desc';
			break;
			case 'date-beginning':
			$this->data['sort_index'] = 'created_at';
			$this->data['sort_value'] = 'asc';
			break;
			case 'price-high':
			$this->data['sort_index'] = 'price';
			$this->data['sort_value'] = 'desc';
			break;
			case 'price-low':
			$this->data['sort_index'] = 'price';
			$this->data['sort_value'] = 'asc';
			break;
			case 'name-asc':
			$this->data['sort_index'] = 'name';
			$this->data['sort_value'] = 'asc';
			break;
			case 'designer-asc':
			$this->data['sort_index'] = 'designer';
			$this->data['sort_value'] = 'asc';
			break;
			case 'designer-desc':
			$this->data['sort_index'] = 'designer';
			$this->data['sort_value'] = 'desc';
			break;
			case 'name-desc':
			$this->data['sort_index'] = 'name';
			$this->data['sort_value'] = 'desc';
			break;
			default:
			$this->data['sort_index'] = 'created_at';
			$this->data['sort_value'] = 'desc';
			break;
		}
	}
	
	public function removeProduct(Request $request) {
		$user = auth()->guard('api')->user();

		$product_id 	= $request->product_id;
		$product_data 	= Products::where('user_id', $user->id)->where('id', $product_id)->first();
		if($product_data) {
			$product_rented 		= Rent::where('product_id', $product_id)->count();
			$product_review 		= ProductUserReview::where('product_id', $product_id)->count();
			$product_wishlist 		= Wishlist::where('product_id', $product_id)->count();
			$product_notification 	= Notification::where('rent_id', $product_id)->count();

			if($product_rented == 0 && $product_review == 0 && $product_wishlist == 0 && $product_notification == 0) {
				Products::deleteData($product_id);
				$product_category_data = ProductCategories::where('product_id', $product_id)->count();
				if($product_category_data > 0) {
					ProductCategories::deleteData($product_id);
				}
				return response()->json([
					'status'    => $this->successStatus,
					'message'   => 'Product has been removed.',
					'data'      => [],
				], $this->successStatus);
			} else {
				return response()->json([
					'status'    => $this->failedStatus,
					'message'   => 'This product can not be deleted, because customers might be using it.',
					'data'      => [],
				], $this->failedStatus);
			}
		}
		return response()->json([
			'status'    => $this->notFoundStatus,
			'message'   => 'Product not found.',
			'data'      => [],
		], $this->notFoundStatus);
	}
}
