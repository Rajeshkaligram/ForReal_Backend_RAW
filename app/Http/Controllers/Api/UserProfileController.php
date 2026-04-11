<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Notifications\RegistrationVerificationCodeSend;
use App\Services\Payments\PayPalAdaptiveService;
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

class UserProfileController extends ApiBaseController {

	public $successStatus = 200;
    public $createdStatus   = 201;
    public $notFoundStatus  = 404;
	public $failedStatus = 422;

	public function getProfile(Request $request) {

		$user = auth()->guard('api')->user();

		$user_details = User::where('id',$user->id)->first(['id', 'first_name', 'last_name', 'contact_number', 'location', 'country', 'longitude', 'latitude', 'birthday', 'email', 'size', 'height', 'breast', 'waist', 'hips', 'body_type', 'profile_picture', 'profile_picture_custom_size', 'paypal_email_address', 'status', 'facebook_id', 'twitter_id']);

		if($user_details) {

			$size = [];

			$size[0]['display'] = "Extra Small";
			$size[1]['display'] = "Small";
			$size[2]['display'] = "Medium";
			$size[3]['display'] = "Large";
			$size[4]['display'] = "Extra Large";
			
			$size[0]['value'] = "Extra Small";
			$size[1]['value'] = "Small";
			$size[2]['value'] = "Medium";
			$size[3]['value'] = "Large";
			$size[4]['value'] = "Extra Large";

			$user_details->display_size = "";

			foreach($size as $value) {
				if($user_details->size==$value['value']) {
					$user_details->display_size = $value['display'];
				}
			}
			
			$height = [];
			$counter = 0;
			for($ft = 4; $ft <= 6; $ft++) {
				for($in = 0; $in <= 11; $in++) {
					$data = $ft."'".$in.'"';
					$height[$counter]['display'] = $data;
					$height[$counter]['value'] = $data;
					$counter++;
				}
			}
			$user_details->display_height = "";
			foreach($height as $value) {
				if($user_details->height==$value['value']) {
					$user_details->display_height = $value['display'];
				}
			}
			
			$breast = [];
			$counter = 0;
			for($in = 20; $in <= 100; $in++) {
				$data = $in.'"';
				$breast[$counter]['display'] = $data;
				$breast[$counter]['value'] = $data;
				$counter++;
			}

			$user_details->display_breast = "";
			foreach($breast as $value) {
				if($user_details->breast==$value['value']) {
					$user_details->display_breast = $value['display'];
				}
			}
			
			$waist= [];
			$counter = 0;
			for($in = 20; $in <= 100; $in++) {
				$data = $in.'"';
				$waist[$counter]['display'] = $data;
				$waist[$counter]['value'] = $data;
				$counter++;
			}

			$user_details->display_waist = "";
			foreach($waist as $value) {
				if($user_details->waist==$value['value']) {
					$user_details->display_waist = $value['display'];
				}
			}	

			$hips= [];
			$counter = 0;
			for($in = 20; $in <= 100; $in++) {
				$data = $in.'"';
				$hips[$counter]['display'] = $data;
				$hips[$counter]['value'] = $data;
				$counter++;
			}

			$user_details->display_hips = ""; 
			foreach($hips as $value) {
				if($user_details->hips==$value['value']) {
					$user_details->display_hips = $value['display'];
				}
			}	 

			$user_details  = $user_details->toArray();

            $user_details['profile_picture'] = env('APP_URL').'/'.$user_details['profile_picture'];
            $user_details['profile_picture_custom_size'] = env('APP_URL').'/'.$user_details['profile_picture_custom_size'];
			$user_details['measurement_image'] = env('APP_URL')."/user-interface/img/size_chart.png";

			return response()->json([
				'status'    => $this->successStatus,
				'message'   => 'User profile',
				'data'      => $user_details,
			], $this->successStatus);

		} else {
			return response()->json([
				'status'    => $this->notFoundStatus,
				'message'   => 'Profile not found.',
				'data'      => [],
			], $this->notFoundStatus);
		}
	}
	
	public function postUpdateFireBaseId(Request $request) {
		$user = auth()->guard('api')->user();

		$rules = []; 
		$rules['firebase_id'] = 'required|min:6';

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json([
				'status'    => $this->failedStatus,
				'message'   => 'Validation Errors.',
				'data'      => $validator->errors(),
			], $this->failedStatus);
		}
		User::where('id',$user->id)->update(['firebase_id'=>$request->firebase_id]);
		return response()->json([
			'status'    => $this->successStatus,
			'message'   => 'Firebase ID updated.',
			'data'      => [],
		], $this->successStatus);
	}
	
		public function postProfile(Request $request) {

			$user = auth()->guard('api')->user();
			$email = trim((string) $request->email);

			if(strlen($email) > 3)
				$check_user = User::where('email', $request->email)->where('id', '!=', $user->id)->first();
			else
				$check_user = [];

		if($check_user) {
	        return response()->json([
	            'status'    => $this->failedStatus,
	            'message'   => 'Email already exist.',
	            'data'      => [],
	        ], $this->failedStatus); 
		} else {
				if(!isset($request->paypal_email_address)) {
					$request->paypal_email_address = '';
				}
				$update_user = User::find($user->id);
				if(strlen(trim((string) $request->first_name)) > 3)
					$update_user->first_name = $request->first_name;
				if(strlen(trim((string) $request->last_name)) > 3)
					$update_user->last_name = $request->last_name;
				if(strlen(trim((string) $request->contact_number)) > 3)
					$update_user->contact_number = $request->contact_number;
				if(strlen(trim((string) $request->location)) > 0)
					$update_user->location = $request->location;
				if(strlen(trim((string) $request->country)) > 0)
					$update_user->country = $request->country;
				if(strlen(trim((string) $request->birthday)) > 0)
					$update_user->birthday = $request->birthday;
				if(strlen(trim((string) $request->size)) > 0)
					$update_user->size = $request->size;
				if(strlen(trim((string) $request->height)) > 0)
					$update_user->height = $request->height;
				if(strlen(trim((string) $request->breast)) > 0)
					$update_user->breast = $request->breast;
				if(strlen(trim((string) $request->waist)) > 0)
					$update_user->waist = $request->waist;
				if(strlen(trim((string) $request->hips)) > 0)
					$update_user->hips = $request->hips;
				if(strlen(trim((string) $request->longitude)) > 3)
					$update_user->longitude = $request->longitude;
				if(strlen(trim((string) $request->paypal_email_address)) > 3)
					$update_user->paypal_email_address = $request->paypal_email_address;
				if(strlen(trim((string) $request->body_type)) > 0)
					$update_user->body_type = $request->body_type;
				if(strlen(trim((string) $request->latitude)) > 3)
					$update_user->latitude = $request->latitude;
			if($request->has('profile_picture')) {

	            // Upload files holds custom old file, custom file size, old file, field name, request, first folder and second folder
				$path = Helper::uploadBase64($request->profile_picture, $user->profile_picture_custom_size, 200, $user->profile_picture, 'profile_picture', $request, 'profile_picture', $user->id);
				$update_user->profile_picture             = $path['new_path'];
				$update_user->profile_picture_custom_size = $path['custom_size_path'];
			} 
				if($user->email != $email) {
					$update_user->status = 0;
					$update_user->verification_code = rand(100000, 1000000);	
				} else 
				$update_user->status = 1;

			$update_user->save();

				if($user->email != $email) {
					$update_user->notify(new RegistrationVerificationCodeSend($update_user->verification_code));
				}

				if($request->has('paypal_email_address')) {
                    $verify = app(PayPalAdaptiveService::class)->verifyEmail($request->paypal_email_address, false);
                    if (!$verify['verified']) {
                        User::where('id', $update_user->id)->update(['verify_paypal_email' => 0]);
                        return response()->json([
                            'status'    => $this->failedStatus,
                            'message'   => 'Paypal account not verified, please verify paypal account.',
                            'data'      => [],
                        ], $this->failedStatus);
                    }
                    User::where('id', $update_user->id)->update(['verify_paypal_email' => 1]);
				} else {
					User::where('id', $update_user->id)->update(['verify_paypal_email' => 0]);
				}

			$user_details = User::where('id',$user->id)->first(['id','first_name','last_name','contact_number','location','country','longitude','latitude','birthday','email','size','height','breast','waist','hips','body_type','profile_picture','profile_picture_custom_size','paypal_email_address','status','facebook_id','twitter_id']);

			if($user_details) {
				$user_details  = $user_details->toArray();

                $user_details['profile_picture'] = env('APP_URL').'/'.$user_details['profile_picture'];
                $user_details['profile_picture_custom_size'] = env('APP_URL').'/'.$user_details['profile_picture_custom_size'];
				$user_details['measurement_image'] = env('APP_URL')."/user-interface/img/size_chart.png";
			}
		}

        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'Profile updated.',
            'data'      => $user_details,
        ], $this->successStatus); 
	}
	
	public function postChangePassword(Request $request) {
		$user = auth()->guard('api')->user();
		$rules = []; 
		$rules['new_password'] = 'required|min:6|different:current_password';
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json([
				'status'    => $this->failedStatus,
				'message'   => 'Validation Errors',
				'data'      => $validator->errors(),
			], $this->failedStatus);
		}
			if(Hash::check($request->current_password, $user->password)) {
				$change_password = User::find($user->id);
				$change_password->password = Hash::make($request->new_password);
				$change_password->crypted_password = Crypt::encrypt($request->new_password);
				$change_password->save();

			return response()->json([
				'status'    => $this->successStatus,
				'message'   => 'Password has been changed.',
				'data'      => [],
			], $this->successStatus);
		} 
		return response()->json([
			'status'    => $this->failedStatus,
			'message'   => 'Invalid current password.',
			'data'      => [],
		], $this->failedStatus);
	}
	
	
}
