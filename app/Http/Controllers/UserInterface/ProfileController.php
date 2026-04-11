<?php

namespace App\Http\Controllers\UserInterface;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Input;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Notifications\EmailVerification;

use App\User;
use App\Models\Helper;
use App\Models\Validators;
use App\Services\Payments\PayPalAdaptiveService;

use DB;
use Auth;
use Crypt;

class ProfileController extends Controller {

	function getIndex() {
		Helper::destroySession();
		$user = Auth::user();

		//echo Crypt::decrypt($user->crypted_password);exit;
		$this->data['countries'] = DB::table( "countries" )
		                             ->orderBy( 'Name', 'asc' )
		                             ->get();

		return view( 'user-interface.dashboard.profile.index', $this->data );
	}

	function getPersonalInfo( $id ) {
		$this->data['user'] = User::where( 'id', Crypt::decrypt( $id ) )->first();

		return view( 'user-interface.dashboard.profile.personal-info', $this->data );
	}

	function postSave( Request $request ) {
		// Send all the request to validate
		$validator = Validators::frontendValidate( $request, "profile_save" );
		// Check the validator if there's no error
		if ( $validator === true ) {
				if ( $request->has( 'paypal_email_address' ) ) {
                    $verify = app(PayPalAdaptiveService::class)->verifyEmail($request->paypal_email_address, false);
                    if (!$verify['verified']) {
                        User::where('id', Auth::user()->id)->update(['verify_paypal_email' => 0]);
                        $error_paypal_email = [];
                        $error_paypal_email['paypal_email_address'][0] = "paypal account not verified. please verify paypal account.";
                        return response()->json(["result" => 'failed', "errors" => $error_paypal_email]);
                    }
                    User::where('id', Auth::user()->id)->update(['verify_paypal_email' => 1]);
				} else {
				User::where( 'id', Auth::user()->id )->update( array( 'verify_paypal_email' => 0 ) );
			}
            $user_old_password = false;
            if(Auth::user()->password != NULL) {
                $user_old_password = User::where('id', Auth::user()->id)->first();
                $user_old_password = Crypt::decrypt($user_old_password->crypted_password);
            }
			$user              = User::manageData( $request, Auth::user()->id );
			if ( Auth::user()->email != $request->email ) {
				User::where( 'id', Auth::user()->id )->update( array( 'status' => 0 ) );
				//print_r($user);exit;

				User::updateCredentials( $request, Auth::user()->id );
				$user = User::where( 'id', Auth::user()->id )->first();
				$user->notify( new EmailVerification( $user, $this->data['configuration'] ) );

				$name = User::displayName( $user->id );
				Auth::logout();

				return response()->json( [
					"result"            => 'success_with_email_update',
					"name"              => $name,
					'user'              => $user,
					'user_old_password' => $user_old_password
				] );
			} else {
				User::updateCredentials( $request, Auth::user()->id );
				$name = User::displayName( $user->id );

				return response()->json( [
					"result"            => 'success',
					"name"              => $name,
					'user'              => $user,
					'user_old_password' => $user_old_password
				] );
			}
		}

		//print_r($validator->errors()->messages());exit;
		return response()->json( [ "result" => 'failed', "errors" => $validator->errors()->messages() ] );
	}

	function getSingle( Request $request, $slug ) {
		$this->data['user_data'] = User::where( 'username', $slug )->first();
		if ( $request->has( 'section' ) && count( $this->data['user_data'] ) ) {
			switch ( $request->section ) {
				case 'profile':
					return view( 'user-interface.dashboard.profile.public.index', $this->data );
					break;
				case 'garments-for-rent':
					return view( 'user-interface.dashboard.profile.public.garments-for-rent', $this->data );
					break;
			}
		}

		return back();
	}

	function getGarments() {

	}

}
