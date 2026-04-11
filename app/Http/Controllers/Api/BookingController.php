<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Rent\Rent;

class BookingController extends ApiBaseController {

	public $successStatus   = 200;
	public $createdStatus   = 201;
	public $notFoundStatus  = 404;
	public $failedStatus    = 422;

	public function getBookingList(Request $request) {
		
		$response = [];
	        
		$product_id = $request->product_id;
		$booking_list = Rent::where('product_id', $product_id)->where('status', '!=', 'Cart')->with(['added_by' => function($query) {
			$query->select('id','first_name','last_name','email','profile_picture','profile_picture_custom_size','location');
		}])->get();
		if(count($booking_list)>0) {
			$booking_list = $booking_list->toArray();
			foreach ($booking_list as $key => $value) {
				$booking_list[$key]['added_by']['profile_picture'] 				= env('APP_URL').'/'.$value['added_by']['profile_picture'];
				$booking_list[$key]['added_by']['profile_picture_custom_size'] 	= env('APP_URL').'/'.$value['added_by']['profile_picture_custom_size'];
			}
			$response['booking_list'] = $booking_list;
			
			return response()->json([
				'status'    => $this->successStatus,
				'message'   => 'Booking list found.',
				'data'      => $response,
			], $this->successStatus);
		}
		
		return response()->json([
			'status'    => $this->notFoundStatus,
			'message'   => 'Booking list not found.',
			'data'      => [],
		], $this->notFoundStatus);
	}
}