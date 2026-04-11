<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Notifications\RegistrationVerificationCodeSend;
use App\User;
use App\Models\Categories;
use App\Models\Products\Products;
use App\Models\DeviceToken;
use App\Models\Wishlist\Wishlist;
use App\Models\ProductUserReview;
use App\Models\Rent\Rent;
use App\Models\Messages\Messages;
use App\Models\Messages\MessagesRoom;
use App\Models\Validators;
use App\Models\Helper;
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;

class WishListController extends ApiBaseController {

	public $successStatus   = 200;
	public $createdStatus   = 201;
	public $notFoundStatus  = 404;
	public $failedStatus    = 422;
	
	public function getWishList(Request $request) {

        $rules = [];
        $rules['results_per_page'] = 'required|integer|min:1|max:1000';

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

			$total = $request->results_per_page;
			$page = max(1, (int) $request->page);
			$skip = ($page - 1) * $total;

		$wishlist 		= Wishlist::where('user_id', $user->id)->orderBy('id', 'desc');
        $whole_wishlist = $wishlist->get()->toArray();
		$wishlist 		= $wishlist->skip($skip)->take($total)->get();

		$product_list = [];
		if(count($wishlist) > 0) {
				foreach($wishlist as $key => $value) {
					$product_detail = Products::where('id',$value->product_id)
					->with(['added_by' => function($query) {
						$query->select('id', 'contact_number', 'location', 'body_type', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size');
					}])
					->first();

					if(!is_object($product_detail)) {
						$value->delete();
					} else {
		                $product_detail['picture'] = env('APP_URL').'/'.$product_detail['picture'];
		                $product_detail['added_by']['profile_picture'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture'];
		                $product_detail['added_by']['profile_picture_custom_size'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture_custom_size'];
						$product_list[] = $product_detail;
					}
				}

			foreach($product_list as $key => $value) {
				if(!is_object($value)) 
					continue; 
				if($user) {
					$check_on_wishlist_or_not = Wishlist::where('product_id', $value->id)->where('user_id', $user->id)->count();
					$value->on_wishlist = 0;
					if($check_on_wishlist_or_not > 0) {
						$value->on_wishlist = 1;
					}
				} else {
					$value->on_wishlist = 0;
				}
				$rating 		= ProductUserReview::where('product_id', $value->id)->avg('rating');                		   	          
				$rating 		= round($rating);
				$value->rating 	= $rating;
			}

			$response['wishlist'] 		= $product_list;
            $response['total'] 			= count($whole_wishlist);
            $response['showing'] 		= count($product_list);
            $response['current_time'] 	= date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Wishlist found.',
                'data'      => $response,
            ], $this->successStatus);
		}
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Wishlist not found.',
            'data'      => [],
        ], $this->notFoundStatus);
	}
}
