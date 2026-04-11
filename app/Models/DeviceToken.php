<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model {

	protected $table = 'user_device_token';
	
	public static function addData($request) {

		if($request->device_type != '' && $request->device_token != '') {
			$check_already_exits = self::where('device_type', $request->device_type)
			->where('device_token', $request->device_token)->first();

			if(!$check_already_exits) {

				$add_new_device_token = new self;
				$add_new_device_token->user_id = $request->user_id;
				$add_new_device_token->device_type = $request->device_type;
				$add_new_device_token->device_token = $request->device_token;
				$add_new_device_token->created_at = date('Y-m-d H:i:s');
				$add_new_device_token->updated_at = date('Y-m-d H:i:s');
				$add_new_device_token->save();
			}
		}
		return true;
	}
	
	public static function RemoveData($request) {

		$check_already_exits = self::where('user_id', $request->user_id)->get();

		if($check_already_exits) {
			$check_already_exits = self::where('user_id',$request->user_id)->delete();
		}
		
		return true;
	}
}