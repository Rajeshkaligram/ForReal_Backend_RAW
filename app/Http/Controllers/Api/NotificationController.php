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
use App\Models\Notification\Notification;
use App\Models\Helper;
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;

class NotificationController extends ApiBaseController {

    public $successStatus   = 200;
    public $createdStatus   = 201;
    public $notFoundStatus  = 404;
    public $failedStatus    = 422;

	public function getNotificationList(Request $request) {

		$user = auth()->guard('api')->user();

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

		$response = [];
		$this->data['sort_index'] = 'notification.created_at';
		$this->data['sort_value'] = 'desc';
		
		if ($request->has('sort')) {
			$this->sort($request);
		}

		if(intval($request->page) <= 1) {
			$request->page = 1;
		}

        $total = $request->results_per_page;
		$skip = ($request->page - 1) * $total;

		$total_notifications = Notification::where('for_user', $user->id)
		->with(['from_user_detail' => function($query) {
			$query->select('id');
		}])
		->get();
		
		$notification = Notification::where('for_user', $user->id)
		->with(['from_user_detail' => function($query) {
			$query->select('id', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size');
		}])
		->orderBy($this->data['sort_index'], $this->data['sort_value'])
		->skip($skip)
		->take($total)
		->get();

			if(count($notification) > 0) {
			$notification = $notification->toArray();
			foreach($notification as $key => $value) {
				$notification[$key]['received'] = Helper::timeDuration($value['created_at']);
				$notification[$key]['from_user_detail']['profile_picture'] = env('APP_URL').'/'.$value['from_user_detail']['profile_picture'];
				$notification[$key]['from_user_detail']['profile_picture_custom_size'] = env('APP_URL').'/'.$value['from_user_detail']['profile_picture_custom_size'];
			}
			$response['notifications'] 	= $notification;
			$response['total'] 			= count($total_notifications);
			$response['showing'] 		= count($notification);
            $response['current_time'] = date('Y-m-d H:i:s');
			// $response['current_page'] 	= intval($request->page);
			// $response['total_pages'] 	= ceil(count($total_notifications) / $total);

			return response()->json([
				'status'    => $this->successStatus,
				'message'   => 'Notifications found.',
				'data'      => $response,
			], $this->successStatus);
		}
		return response()->json([
			'status'    => $this->notFoundStatus,
			'message'   => 'Notification not found.',
			'data'      => [],
		], $this->notFoundStatus);
	}
	
	public function sort($request) {

		switch ($request->sort) {
			case 'date-recently':
			$this->data['sort_index'] = 'notification.created_at';
			$this->data['sort_value'] = 'desc';
			break;
			case 'date-beginning':
			$this->data['sort_index'] = 'notification.created_at';
			$this->data['sort_value'] = 'asc';
			break;
			default:
			$this->data['sort_index'] = 'notification.created_at';
			$this->data['sort_value'] = 'desc';
			break;
		}
	}
}
