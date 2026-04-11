<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Categories;
use App\Models\Products\Products;

class CommonDropdownController extends ApiBaseController {

	public $successStatus   = 200;
	public $createdStatus   = 201;
	public $notFoundStatus  = 404;
	public $failedStatus    = 422;

	public function getCommonDropdown(Request $request) {
		
		$response = [];
		
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
			
		$response['SIZE'] = $size;
		//--------- End
			
			
		$shipping_state_province_code = [];
			$shipping_state_province_code [0]['display'] = "Alberta, CA";
			$shipping_state_province_code [1]['display'] = "British Columbia, CA";
			$shipping_state_province_code [2]['display'] = "Manitoba, CA";
			$shipping_state_province_code [3]['display'] = "New Brunswick, CA";
			$shipping_state_province_code [4]['display'] = "Newfoundland and Labrador, CA";
			$shipping_state_province_code [5]['display'] = "Northwest Territories, CA";
			$shipping_state_province_code [6]['display'] = "Nova Scotia, CA";
			$shipping_state_province_code [7]['display'] = "Nunavut, CA";
			$shipping_state_province_code [8]['display'] = "Ontario, CA";
			$shipping_state_province_code [9]['display'] = "Prince Edward Island, CA";
			$shipping_state_province_code [10]['display'] = "Quebec, CA";
			$shipping_state_province_code [11]['display'] = "Saskatchewan, CA";
			$shipping_state_province_code [12]['display'] = "Yukon, CA";
			
			$shipping_state_province_code [0]['value'] = "AB";
			$shipping_state_province_code [1]['value'] = "BC";
			$shipping_state_province_code [2]['value'] = "MB";
			$shipping_state_province_code [3]['value'] = "NB";
			$shipping_state_province_code [4]['value'] = "NL";
			$shipping_state_province_code [5]['value'] = "NT";
			$shipping_state_province_code [6]['value'] = "NS";
			$shipping_state_province_code [7]['value'] = "NU";
			$shipping_state_province_code [8]['value'] = "ON";
			$shipping_state_province_code [9]['value'] = "PE";
			$shipping_state_province_code [10]['value'] = "QC";
			$shipping_state_province_code [11]['value'] = "SK";
			$shipping_state_province_code [12]['value'] = "YT";
		
			$response['SHIPPING_STATE_PROVINCE_CODE'] = $shipping_state_province_code ;
		//--------- End


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

			$response['HEIGHT'] = $height;                                      
		//--------- End
			

		$shipping_country = [];
			$shipping_country [0]['value'] = "CA";
			$shipping_country [0]['display'] = "Canada";
			
			$response['SHIPPING_COUNTRY'] = $shipping_country;    
		//--------- End
			
			
		$shipping_weight = [];  
			$shipping_count = 0;
				 for($in = 1; $in <= 30; $in++) {
			$shipping_weight[$shipping_count]['display'] 	= $in;
			$shipping_weight[$shipping_count]['value'] 		= $in;
				 	$shipping_count++;
				 }
			
		$response['SHIPPING_WEIGHT'] = $shipping_weight;
		//--------- End


		$breast = [];
			$counter = 0;
				 for($in = 20; $in <= 100; $in++) {
				 	$data = $in.'"';
				 	$breast[$counter]['display'] = $data;
				 	$breast[$counter]['value'] = $data;
				 	$counter++;
				 }
			
			$response['BREAST'] = $breast;                                      
		//--------- End
		
		
		$waist = [];
			$counter = 0;
				 for($in = 20; $in <= 100; $in++) {
				 	$data = $in.'"';
			$waist[$counter]['display'] 	= $data;
			$waist[$counter]['value'] 		= $data;
				 	$counter++;
				 }
			
		$response['WAIST'] = $waist;
		//--------- End
		
		
		$hips = [];
			$counter = 0;
				 for($in = 20; $in <= 100; $in++) {
				 	$data = $in.'"';
			$hips[$counter]['display'] 	= $data;
			$hips[$counter]['value'] 	= $data;
				 	$counter++;
				 }
		$response['HIPS'] = $hips;
		//--------- End
			

		$rented_filter = [];
			$rented_filter [0]['display'] = "Date (Recently)";
			$rented_filter [1]['display'] = "Date (Beginning)";
			$rented_filter [2]['display'] = "Price (High)";
			$rented_filter [3]['display'] = "Price (Low)";
			$rented_filter [4]['display'] = "Name (Asc)";
			$rented_filter [5]['display'] = "Name (Desc)";
			
			$rented_filter [0]['value'] = "date-recently";
			$rented_filter [1]['value'] = "date-beginning";
			$rented_filter [2]['value'] = "price-high";
			$rented_filter [3]['value'] = "price-low";
			$rented_filter [4]['value'] = "name-asc";
			$rented_filter [5]['value'] = "name-desc";

			$response['RENTED_FILTER'] = $rented_filter;  
		//--------- End
			

		$notification_filter = [];
			$notification_filter[0]['display'] = "Date (Recently)";
			$notification_filter[1]['display'] = "Date (Beginning)";
			
			$notification_filter[0]['value'] = "date-recently";
			$notification_filter[1]['value'] = "date-beginning";
			$response['NOTIFICATION_FILTER'] = $notification_filter;  
		//--------- End
			
			
		$price = [];
			$price['price1'] = "1";
			$price['price2'] = Products::max('price');                                   
			
			$response['PRICE'] = $price;  
		//--------- End
			

		$season = [];
			$season[0]['display'] = "Spring";
			$season[1]['display'] = "Summer";
			$season[2]['display'] = "Fall";
			$season[3]['display'] = "Winter";
			
			$season[0]['value'] = "spring";
			$season[1]['value'] = "summer";
			$season[2]['value'] = "fall";
			$season[3]['value'] = "winter";
			$response['SEASON'] = $season;  
		//--------- End
			
			
		$body_type = [];
		$body_type[0]['display'] = env('APP_URL')."/user-interface/img/body-type-new-1.png";
		$body_type[1]['display'] = env('APP_URL')."/user-interface/img/body-type-new-2.png";
		$body_type[2]['display'] = env('APP_URL')."/user-interface/img/body-type-new-3.png";
		$body_type[3]['display'] = env('APP_URL')."/user-interface/img/body-type-new-4.png";
		$body_type[4]['display'] = env('APP_URL')."/user-interface/img/body-type-new-5.png";
			
			$body_type[0]['value'] = "1";
			$body_type[1]['value'] = "2";
			$body_type[2]['value'] = "3";
			$body_type[3]['value'] = "4";
			$body_type[4]['value'] = "5";
			$response['BODY_TYPE'] = $body_type;  
		//--------- End

			
			$category = Categories::where('status',1)
                                            ->orderBy('name','asc')->select('id as value','name as display')
		->get(['id', 'name']);
                                            
			$response['CATEGORY'] = $category ;  
		//--------- End

			
		$alternation = [];
			$alternation[0]['display'] = "Yes";
			$alternation[1]['display'] = "No";
			
			$alternation[0]['value'] = "Yes";
			$alternation[1]['value'] = "No";
			
			$response['ALTERATION'] = $alternation;  
		//--------- End
			
			
		$condition = [];
		$condition[0]['display'] = "Like New";
		$condition[1]['display'] = "Slightly Worn";
		$condition[2]['display'] = "Still Looks Good";
			
		$condition[0]['value'] = "Like New";
		$condition[1]['value'] = "Slightly Worn";
		$condition[2]['value'] = "Still Looks Good";

		$response['CONDITION'] = $condition;
		//--------- End
			
			
		$cancellation = [];
		$cancellation[0]['display'] = "Aggressive (Item may be cancelled without penalty 6-8 days prior the rental period)";
		$cancellation[1]['display'] = "Moderate (Bookings may be canceled without penalty more than ten (10) days before the beginning of the Rental Period)";
			
		$cancellation[0]['value'] = "Aggressive (Item may be cancelled without penalty 6-8 days prior the rental period)";
		$cancellation[1]['value'] = "Moderate (Bookings may be canceled without penalty more than ten (10) days before the beginning of the Rental Period)";
			
		$response['CANCELLATION'] = $cancellation ;
		//--------- End
			
			
		$contact_us_category = [];
			$contact_us_category[0]['display'] = "I received the items damaged";
			$contact_us_category[1]['display'] = "I received the item late";
			$contact_us_category[2]['display'] = "I received the wrong item";
			$contact_us_category[3]['display'] = "Technical problems with the website";
			
			$contact_us_category[0]['value'] = "I received the items damaged";
			$contact_us_category[1]['value'] = "I received the item late";
			$contact_us_category[2]['value'] = "I received the wrong item";
			$contact_us_category[3]['value'] = "Technical problems with the website";

			$response['CONTACT_US_CATEGORY'] = $contact_us_category;  
		//--------- End
		
		
		return response()->json([
			'status'    => $this->successStatus,
			'message'   => 'Common dropdowns.',
			'data'      => $response,
		], $this->successStatus);
	}
}