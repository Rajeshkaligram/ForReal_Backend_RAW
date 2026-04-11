<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Notifications\RegistrationVerificationCodeSend;
use App\Notifications\ResendVerificationCode;
use App\User;
use App\Models\Categories;
use App\Models\Products\Products;
use App\Models\DeviceToken;
use App\Models\Wishlist\Wishlist;
use App\Models\ProductUserReview;
use App\Models\Rent\Rent;
use App\Models\Messages\Messages;
use App\Models\Messages\MessagesRoom;
use App\Models\Cleaner;
use App\Models\Configuration;
use App\Models\FAQs;
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;
use Builder;

class UserController extends ApiBaseController {

    public $successStatus   = 200;
    public $createdStatus   = 201;
    public $notFoundStatus  = 404;
    public $failedStatus    = 422;

    public function register(Request $request) {

        $validator = Validator::make($request->all(), [ 
            'first_name'    => 'required|max:15',
            'last_name'     => 'required|max:15',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

        $check_user = User::where('email', $request->email)->get();

        if (count($check_user) == 0 || $check_user->status == '0') {
            $user = User::manageData($request);

            $request->user_id = $user->id; //putting user_id into $request
            DeviceToken::addData($request);

            $user_details = User::where('id', $user->id)->first(['id', 'verification_code', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size']);
            $user_details = $user_details->toArray();

            $user_details['profile_picture'] = env('APP_URL').'/'.$user_details['profile_picture'];
            $user_details['profile_picture_custom_size'] = env('APP_URL').'/'.$user_details['profile_picture_custom_size'];

            if (config('access.users.confirm_email')) {
                $user->notify(new RegistrationVerificationCodeSend($user_details['verification_code']));
            }

            unset($user_details['verification_code']);

            return response()->json([
                'status'    => $this->createdStatus,
                'message'   => 'Your account was successfully created. We have sent you an e-mail to confirm your account.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $user_details),
            ], $this->createdStatus);

        } else {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Could not register',
                'data'      => [],
            ], $this->failedStatus);
        }
    }

    public function postLogout(Request $request) {
        $user = auth()->guard('api')->user();

        if (Auth::check()) {
            $request->user_id = $user->id;
            DeviceToken::RemoveData($request);
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->update(['revoked' => 1]);
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Logged-out Successfully.',
                'data'      => [],
            ], $this->successStatus); 
        } else {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Something went wrong!',
                'data'      => [],
            ], $this->failedStatus);
        }
    }

    public function postSigninSocial(Request $request) {

        if ($request->type == "facebook") {
            $check_email_exists = User::where('email', $request->email)->first();
            if ($check_email_exists) {
                if ($check_email_exists->facebook_id == NULL && $check_email_exists->twitter_id == NULL) {
                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Email already registered, please login with email and password.',
                        'data'      => [],
                    ]); 
                } else if ($check_email_exists->twitter_id != '') {
                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Email already registered with twitter, please login with twitter.',
                        'data'      => [],
                    ]); 
                } else {
                    $user_details = User::where('id', $check_email_exists->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size', 'firebase_id']);
                    $request->user_id = $user_details->id;
                    $user_details = $user_details->toArray();
                    $user_details = array_map(function($v) {
                        return (is_null($v)) ? '' : $v;
                    }, $user_details);

                    DeviceToken::addData($request);
                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Facebook login successfully.',
                        'data'      => array_merge(['api_token' => $check_email_exists->createToken(env('APP_NAME'))->accessToken], $user_details),
                    ]); 
                }
            } else {
                $user = new User;
                $user->facebook_id = $request->social_id;
                $user->email = $request->email;
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->status = 1;
                $user->profile_picture = '/uploads/others/no_avatar.jpg';
                $user->profile_picture_custom_size = '/uploads/others/no_avatar.jpg';
                $user->save();

                $user_details = User::where('id', $user->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size', 'firebase_id']);

                $request->user_id = $user_details->id;
                $user_details = $user_details->toArray();
                $user_details = array_map(function($v) {
                    return (is_null($v)) ? '' : $v;
                }, $user_details);

                DeviceToken::addData($request);
                return response()->json([
                    'status'    => $this->successStatus,
                    'message'   => 'Facebook registered successfully.',
                    'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $user_details),
                ]); 
            }

            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Invalid email address.',
                'data'      => [],
            ]); 

        } else {
            $check_email_exists = User::where('email', $request->email)->first();
            if ($check_email_exists) {
                if ($check_email_exists->facebook_id == NULL && $check_email_exists->twitter_id == NULL) {
                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Email already registered, please login with email and password.',
                        'data'      => [],
                    ]); 
                } else if ($check_email_exists->facebook_id != '') {
                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Email already registered with facebook, please login with facebook.',
                        'data'      => [],
                    ]); 
                } else {
                    $code = SUCCESS;
                    $msg = "Twitter login successfully.";

                    $user_details = User::where('id', $check_email_exists->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size', 'firebase_id']);

                    $request->user_id = $user_details->id;
                    $user_details = $user_details->toArray();
                    $user_details = array_map(function($v) {
                        return (is_null($v)) ? '' : $v;
                    }, $user_details);

                    DeviceToken::addData($request);

                    return response()->json([
                        'status'    => $this->successStatus,
                        'message'   => 'Twitter login successfully.',
                        'data'      => array_merge(['api_token' => $check_email_exists->createToken(env('APP_NAME'))->accessToken], $user_details),
                    ]); 
                }

            } else {
                $user = new User;
                $user->twitter_id = $request->social_id;
                $user->email = $request->email;
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->status = 1;
                $user->profile_picture = '/uploads/others/no_avatar.jpg';
                $user->profile_picture_custom_size = '/uploads/others/no_avatar.jpg';
                $user->save();

                $user_details = User::where('id', $user->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size', 'firebase_id']);

                $request->user_id = $user_details->id;
                $user_details = $user_details->toArray();
                $user_details = array_map(function($v) {
                    return (is_null($v)) ? '' : $v;
                }, $user_details);

                DeviceToken::addData($request);
            }
        }

        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'Twitter registered successfully.',
            'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $user_details),
        ]); 
    }

    public function login(Request $request) {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user();
            if($user->status == 1) {
            $check_user = User::where('email', $request->email)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size', 'firebase_id']);
            
            if ($check_user) {
                $request->user_id = $check_user->id;
                $check_user = $check_user->toArray();

                $check_user['profile_picture'] = env('APP_URL').'/'.$check_user['profile_picture'];
                $check_user['profile_picture_custom_size'] = env('APP_URL').'/'.$check_user['profile_picture_custom_size'];

                $response['user_details'] = $check_user;
                DeviceToken::addData($request);
            }
                return response()->json([
                    'status'    => $this->successStatus,
                    'message'   => 'You are logged in',
                    'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $check_user) ,
                ], $this->successStatus);
            }
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'We have sent you an account activation code. Please check your email.',
                'data'      => [],
            ], $this->failedStatus); 
        }
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'These credentials do not match our records.',
            'data'      => [],
        ], $this->failedStatus); 
    }

    public function postContactUs(Request $request) {

        $user = auth()->guard('api')->user();
        $rules = [];

        $rules['name']          = 'required|min:3';
        $rules['email_address'] = 'required|email';
        // $rules['localization']       = 'required|min:2';
        $rules['subject']       = 'required|min:3';
        $rules['message']       = 'required|min:10';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        Configuration::contactUs($request);

        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'Contact us form submitted.',
            'data'      => [],
        ], $this->successStatus);
    }

    public function getFAQsList() {
        $user = auth()->guard('api')->user();
        $response = [];

        $faqs_list = FAQs::all();
        
        if (count($faqs_list) > 0) {
            $response['faqs_list'] = $faqs_list;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'FAQs found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'FAQs not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function postResendVerificationCode(Request $request) {

        $user   = auth()->guard('api')->user();
        $verification_code = rand(100000, 1000000);

        User::where('id', $user->id)->update(['verification_code' => $verification_code]);
        $user = User::where('id', $user->id)->first();
        $user_details = User::where('id', $user->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size']);

        $user_details = $user_details->toArray();
        
        $user_details['profile_picture'] = env('APP_URL').'/'.$user_details['profile_picture'];
        $user_details['profile_picture_custom_size'] = env('APP_URL').'/'.$user_details['profile_picture_custom_size'];

        $user->notify(new ResendVerificationCode($verification_code));

        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'Verification code sent successfully, please check your email.',
            'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $user_details),
        ]);
    }

    public function postVerifyCode(Request $request) {

        $user = auth()->guard('api')->user();

        $check_verify_code = User::where('verification_code', $request->verification_code)->where('id', $user->id)->first();

        if ($check_verify_code) {

            User::where('id', $user->id)->update(['verification_code' => 0, 'status' => '1']);
            $user_details = User::where('id', $user->id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size']);
            $user_details = $user_details->toArray();

            $user_details['profile_picture'] = env('APP_URL').'/'.$user_details['profile_picture'];
            $user_details['profile_picture_custom_size'] = env('APP_URL').'/'.$user_details['profile_picture_custom_size'];

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'User verified successfully.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $user_details),
            ], $this->successStatus);

        }
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Invalid verification code.',
            'data'      => [],
        ], $this->failedStatus);
    }

    public function postSigninFacebook(Request $request) {

        $check_user = new User;

        if (isset($request->email)) {
            $check_user = $check_user->where('email', $request->email);
        }

        $check_user = $check_user->where('facebook_id', $request->facebook_id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size']);

        if ($check_user) {
            $user = User::find($check_user->id);
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Facebook login successfully.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $check_user),
            ]);
        } else {
            $user = new User;
            $user->facebook_id = $request->facebook_id;
            $user->email = $request->email;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();
            $check_user = User::find($user->id);
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Facebook registered successfully.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $check_user),
            ]);
        }

        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Invalid email address.',
            'data'      => [],
        ]);
    }

    public function postSigninTwitter(Request $request) {

        $check_user = new User;

        if (isset($request->email)) {
            $check_user = $check_user->where('email', $request->email);
        }
        $check_user = $check_user->where('twitter_id', $request->twitter_id)->first(['id', 'status', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size']);
        if ($check_user) {
            $user = User::find($check_user->id);
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Twitter login successfully.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $check_user),
            ]);
        } else {
            $user = new User;
            $user->twitter_id = $request->twitter_id;
            $user->email = $request->email;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();
            $check_user = User::find($user->id);
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Twitter registered successfully.',
                'data'      => array_merge(['api_token' => $user->createToken(env('APP_NAME'))->accessToken], $check_user),
            ]);
        }
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Invalid email address.',
            'data'      => [],
        ]);
    }

    public function postForgotpassword(Request $request) {

        $rules = [];
        $rules['email'] = 'required|email'; 
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ]);
        }

        $success = User::forgotPassword($request);

        if ($success == 'social media') {
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Email is registered with social media. please login with social media.',
                'data'      => [],
            ], $this->successStatus);
        } elseif ($success == 'true') {
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'A password reset link has been sent to your email address.',
                'data'      => [],
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Invalid email address.',
            'data'      => [],
        ], $this->failedStatus);
    }

    public function getCategoryList() {

        $category_list  = Categories::where('status', 1)->get(['id', 'name', 'picture']);
        $response = [];

        foreach ($category_list as $value) {
            $value->picture = env('APP_URL').'/'.$value->picture;
        }

        if (!$category_list) {
            return response()->json([
                'status'    => $this->notFoundStatus,
                'message'   => 'Category not found.',
                'data'      => [],
            ], $this->notFoundStatus);
        }

        $response['categories'] = $category_list;
        $response['total']      = count($category_list);

        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'All product categories.',
            'data'      => $response,
        ], $this->successStatus);
    }

    public function getReviewList(Request $request) {

        $rules = [];
        $rules['product_id'] = 'required|integer|min:1'; 
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        $response = [];

        $review_list = ProductUserReview::where('product_id', $request->product_id)
        ->with(['reviewed_by' => function($query) {
            $query->select("id", "contact_number", "location", "body_type", "first_name", "last_name", "profile_picture", "profile_picture_custom_size");
        }])->get();

        if (count($review_list) > 0) {
            $review_list = $review_list->toArray();
            foreach($review_list as $key => $value) {
                $review_list[$key]['reviewed_by']['profile_picture'] = env('APP_URL').'/'.$value['reviewed_by']['profile_picture'];
                $review_list[$key]['reviewed_by']['profile_picture_custom_size'] = env('APP_URL').'/'.$value['reviewed_by']['profile_picture_custom_size'];
            }

            $response['review_list'] = $review_list;
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Reviews found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Reviews not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function submitProductReview(Request $request) {

        $rules = [];
        $rules['product_id']    = 'required|integer|min:1'; 
        $rules['rating']        = 'integer|min:1|max:5'; 

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

        $user = auth()->guard('api')->user();
        $request->user_id = $user->id;

        $product_detail = Products::where('id', $request->product_id)->first();
        if ($product_detail) {
            $review_added = ProductUserReview::manageDataUsingAPI($request);
            if ($review_added) {
                Rent::where('product_id', $request->product_id)->where('user_id', $user->id)->update(['user_review_submitted' => 1]);
                $user_device_token = DeviceToken::where('user_id', $product_detail->user_id)->get();
            }
        } else {
            return response()->json([
                'status'    => $this->notFoundStatus,
                'message'   => 'Product not found.',
                'data'      => [],
            ], $this->notFoundStatus);
        }

        if ($user_device_token) {
            foreach ($user_device_token as $key => $value) {
                if ($value->device_type == "Android") {
                    $fields = [
                        'to'    => $value->device_token,
                        'data'  => ['message' => 'Review Submitted', 'title' => 'A user submitted review to your product.']
                    ];
                    sendPushNotification($fields);
                }
            }
        } else {
            return response()->json([
                'status'    => $this->notFoundStatus,
                'message'   => 'Device not found.',
                'data'      => [],
            ], $this->notFoundStatus);
        }
        return response()->json([
            'status'    => $this->successStatus,
            'message'   => 'Review submitted successfully.',
            'data'      => [],
        ], $this->successStatus);
    }

    public function getProductSearch(Request $request) {

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
        $total = $request->results_per_page;
        $page = max(1, (int) $request->page);
        $skip = ($page - 1) * $total;

        $product_list = Products::where('name', "LIKE", "%" . $request->search . "%")
        ->orderBy('created_at', 'desc')
        ->with(['added_by' => function($query) {
            $query->select('id', 'first_name', 'last_name');
        }]);
        $all_product_list = $product_list->get()->toArray();
        $product_list = $product_list->skip($skip)->take($total)->get(['id', 'user_id', 'name', 'price', 'picture']);
        
        if (count($product_list) > 0) {
            foreach ($product_list as $key => $value) {
                $value->picture = env('APP_URL').'/'.$value->picture;
                $value->is_rented = $value->isRented(date('m/d/Y'), $value->id) ;

                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $value->rating = $rating;
            }
            $product_list = $product_list->toArray();

            $response['products'] = $product_list;
            $response['total'] = count($all_product_list);
            $response['showing'] = count($product_list);
            $response['current_time'] = date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Products found.',
                'data'      => $response,
            ], $this->successStatus);
        }

        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'No product found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getNewProducts(Request $request) {

        $rules = [];
        $rules['from_date'] = 'required|date_format:Y-m-d';
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
        $total = $request->results_per_page;
        $page = max(1, (int) $request->page);
        $skip = ($page - 1) * $total;

        $product_list = Products::whereDate('created_at', '>=', $request->from_date)
        ->orderBy('created_at', 'desc')
        ->with(['added_by' => function($query) {
            $query->select('id', 'first_name', 'last_name');
        }]);

        $all_product_list = $product_list->get()->toArray();

        $product_list = $product_list
        ->skip($skip)
        ->take($total)
        ->get(['id', 'user_id', 'name', 'price', 'picture']);

        if (count($product_list) > 0) {
            foreach ($product_list as $key => $value) {
                $value->picture = env('APP_URL').'/'.$value->picture;

                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $value->rating = $rating;
                $value->added_by->last_name = substr($value->added_by->last_name, 0, 1);
            }
            $product_list = $product_list->toArray();

            $response['products']       = $product_list;
            $response['total']          = count($all_product_list);
            $response['showing']        = count($product_list);
            $response['current_time']   = date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Products found.',
                'data'      => $response,
            ], $this->successStatus);
        }

        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'No product found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getProductList(Request $request) {

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
        $total = $request->results_per_page; // results per page
        $page = max(1, (int) $request->page);
        $skip = ($page - 1) * $total;
        $total_products = Products::count();

        $product_list = Products::groupBy('products.id', 'products.created_at')
        ->leftjoin('product_categories', 'products.id', '=', 'product_categories.product_id')
        ->leftjoin('categories', 'product_categories.category_id', '=', 'categories.id')
        ->leftjoin('users', 'products.user_id', '=', 'users.id')
        ->select('products.id as productID')
        ->orderBy('products.created_at', 'desc')
        ->skip($skip)->take($total)->get()->toArray();

        $product_list_id = collect($product_list)->pluck('productID')->all();

        $product_list = Products::whereIn('id', $product_list_id)->with(['added_by' => function($query) {
            $query->select('id', 'first_name', 'last_name');
        }])->orderBy('created_at', 'desc')->get(['id', 'user_id', 'name', 'price', 'picture']);

        if (count($product_list) > 0) {
            foreach ($product_list as $key => $value) {
                $value->picture     = env('APP_URL').'/'.$value->picture;
                $value->is_rented   = $value->isRented(date('m/d/Y'), $value->id);

                $value->category = Categories::join('product_categories', 'categories.id', '=', 'product_categories.category_id')
                ->where('categories.status', 1)
                ->where('product_categories.product_id', $value->id)
                ->orderBy('categories.name', 'asc')
                ->get(['category_id', 'name'])->first();

                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $value->rating = $rating;
                $value->added_by->last_name = substr($value->added_by->last_name, 0, 1);
            }
            $product_list = $product_list->toArray();

            $response['products']       =   $product_list;
            $response['total']          =   $total_products;
            $response['showing']        =   count($product_list);
            $response['current_time']   =   date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Products found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Products not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getProductListFilter(Request $request) {

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
        $total = $request->results_per_page;
        $page = max(1, (int) $request->page);
        $skip = ($page - 1) * $total;

        $category_id = $request->category_id;

        $this->data['body_type'] = '';
        $this->data['size'] = '';
        $this->data['price'] = Products::max('price');
        $this->data['per'] = 1;
        $this->data['location'] = '';
        $this->data['designer'] = '';
        $this->data['height'] = '';
        $this->data['season'] = '';
        $this->data['category'] = '';
        $max_product_price = Products::max('price');
        if ($max_product_price == '') {
            $max_product_price = 1;
        }
        $this->data['max_product_price'] = $max_product_price;
        $this->data['price1'] = 1;
        $this->data['price2'] = $max_product_price;

        if ($request->has('body_type')) {
            $this->data['body_type'] = $request->body_type;
        }
        if ($request->has('size')) {
            $this->data['size'] = $request->size;
        }
        if ($request->has('price_min')) {
            $this->data['price1'] = $request->price_min;
        }
        if ($request->has('price_max')) {
            $this->data['price2'] = $request->price_max;
        }
        if ($request->has('per')) {
            $this->per($request);
        }
        if ($request->has('location')) {
            $this->data['location'] = $request->location;
        }
        if ($request->has('designer')) {
            $this->data['designer'] = $request->designer;
        }
        if ($request->has('height')) {
            $this->data['height'] = $request->height;
        }
        if ($request->has('season')) {
            $this->data['season'] = $request->season;
        }

        $price1 = filter_var($this->data['price1'], FILTER_VALIDATE_FLOAT);
        $price2 = filter_var($this->data['price2'], FILTER_VALIDATE_FLOAT);

        if ($price1 === false || $price1 === null) { $price1 = 1; }
        if ($price2 === false || $price2 === null) { $price2 = $max_product_price; }

        $price1 = max(1, (float) $price1);
        $price2 = max($price1, (float) $price2);

        $this->data['price1'] = $price1;
        $this->data['price2'] = $price2;

        $category_name = Categories::where('id', $category_id)->first();
        if ($category_name) {
            $this->data['category'] = $category_name->name;
        }

        $this->data['budget'] = $this->data['price'];

        $product_list = Products::groupBy('products.id', 'products.created_at')
        ->leftjoin('product_categories', 'products.id', '=', 'product_categories.product_id')
        ->leftjoin('categories', 'product_categories.category_id', '=', 'categories.id')
        ->leftjoin('users', 'products.user_id', '=', 'users.id');

        if($category_id > 0) {
            $product_list = $product_list->where('categories.id', '=', $category_id);
            $product_list = $product_list->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%');
        }
        if ($this->data['size'] != '') {
            $product_list = $product_list->where('products.size', $this->data['size']);
        }
        $request_product_list = $product_list
        ->where('products.price', '>=', $this->data['price1'])
        ->where('products.price', '<=', $this->data['price2'])
        ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
        ->where('products.designer', 'LIKE', '%' . $this->data['designer'] . '%')
        ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
        ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
        ->select('products.id as productID')
        ->orderBy('products.created_at', 'desc');

        $all_product_in_request = [];
        $all_product_in_request = $request_product_list->get()->toArray();
        $product_list = $request_product_list->skip($skip)->take($total)->get()->toArray();

        $product_list_id = collect($product_list)->pluck('productID')->all();

        $product_list = Products::whereIn('id', $product_list_id)
        ->with(['added_by' => function($query) {
            $query->select('id', 'first_name', 'last_name');
        }])
        ->orderBy('created_at', 'desc')
        ->get(['id', 'user_id', 'name', 'price', 'picture']);

        if (count($product_list) > 0) {
            foreach ($product_list as $key => $value) {
                $value->is_rented = $value->isRented(date('m/d/Y'), $value->id);
                $value->picture = env('APP_URL').'/'.$value->picture;

                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $product_list[$key]->rating = $rating;
                $product_list[$key]->added_by->last_name = substr($product_list[$key]->added_by->last_name, 0, 1);
            }
            $product_list = $product_list->toArray();

            $response['products']       = $product_list;
            $response['total']          = count($all_product_in_request);
            $response['showing']        = count($product_list);
            $response['current_time']   = date('Y-m-d H:i:s');

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Products found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Products not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function productAddToWishlist(Request $request) {
        $user = auth()->guard('api')->user();

        $response   = [];
        $rules      = [];
        $rules['product_id'] = 'required|integer|min:1';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        $product_detail = Products::where('id', $request->product_id)->first();

        if ($product_detail) {
            $foundInWishlist = Wishlist::where('user_id', $user->id)->where('product_id', $request->product_id)->get();
            if (count($foundInWishlist) > 0) {
                return response()->json([
                    'status'    => $this->failedStatus,
                    'message'   => 'Product has already been added to the wishlist.',
                    'data'      => [],
                ], $this->failedStatus);
            }
            //restricts adding product multiple times in wishlist.
            // Wishlist::where('product_id', $request->product_id)->where('user_id', $user->id)->delete();
            else {
                $add_to_wishlist = new Wishlist;
                $add_to_wishlist->product_id    = $request->product_id;
                $add_to_wishlist->user_id       = $user->id;
                $add_to_wishlist->save();

                $wishlist = Wishlist::where('user_id', $user->id)->get()->toArray();

                $product_ids = collect($wishlist)->pluck('product_id')->all();

                $product_list = Products::whereIn('id', $product_ids)
                ->with(['added_by' => function($query) {
                    $query->select('id', 'first_name', 'last_name');
                }])
                ->orderBy('created_at', 'desc')
                ->get(['id', 'user_id', 'name', 'price', 'picture']);

                foreach ($product_list as $key => $value) {
                    unset($value['user_id']);
                    $value['picture'] = env('APP_URL').'/'.$value['picture'];
                }
                $response['wishlist'] = $product_list;

                return response()->json([
                    'status'    => $this->createdStatus,
                    'message'   => 'Product added to wishlist.',
                    'data'      => $response,
                ], $this->createdStatus);
            }
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function productRemoveFromWishlist(Request $request) {
        $user = auth()->guard('api')->user();

        $response   = [];
        $rules      = [];
        $rules['product_id'] = 'required|integer|min:1';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        $product_detail = Products::where('id', $request->product_id)->first();

        if ($product_detail) {
            $deleted = Wishlist::where('product_id', $request->product_id)->where('user_id', $user->id)->delete();
            if ($deleted) {

                $wishlist = Wishlist::where('user_id', $user->id)->get()->toArray();

                $product_ids = collect($wishlist)->pluck('product_id')->all();

                $product_list = Products::whereIn('id', $product_ids)
                ->with(['added_by' => function($query) {
                    $query->select('id', 'first_name', 'last_name');
                }])
                ->orderBy('created_at', 'desc')
                ->get(['id', 'user_id', 'name', 'price', 'picture']);

                foreach ($product_list as $key => $value) {
                    unset($value['user_id']);
                    $value['picture'] = env('APP_URL').'/'.$value['picture'];
                }
                $response['wishlist'] = $product_list;

                return response()->json([
                    'status'    => $this->successStatus,
                    'message'   => 'Product removed from wishlist.',
                    'data'      => $response,
                ], $this->successStatus);
            }
            return response()->json([
                'status'    => $this->notFoundStatus,
                'message'   => 'Product not in wishlist.',
                'data'      => [],
            ], $this->notFoundStatus);
            
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getProductDetail(Request $request) {

        $rules = [];
        $rules['product_id'] = 'required|integer|min:1';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        
        $total          = $request->total;
        $skip           = $request->skip;
        $response       = [];
        $product_id     = $request->product_id;

        $product_detail = Products::where('id', $product_id)->with(['added_by' => function($query) {
            $query->select("id", "contact_number", "location", "body_type", "first_name", "last_name", "profile_picture", "profile_picture_custom_size", "firebase_id");
        }, 
        'product_photos', 
        'reviews' => function($query) {
            $query->orderBy('id', 'desc')->with(['user_detail' => function($query1) {
                $query1->select("id", "first_name", "last_name", "profile_picture", "profile_picture_custom_size");
            }]);
        }])->first();

        if ($product_detail) {
            $isRented = $product_detail->isRented(date('m/d/Y'), $product_detail->id);

        // Prepending env('APP_URL') to make links absolute.
            $product_detail['picture'] = env('APP_URL').'/'.$product_detail['picture'];
            $product_detail['added_by']['profile_picture'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture'];
            $product_detail['added_by']['profile_picture_custom_size'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture_custom_size'];

            if ($product_detail['product_photos']) {
                foreach ($product_detail['product_photos'] as $photo) {
                    $photo->sub_photo = env('APP_URL').'/'.$photo->sub_photo;
                }
            }
        }

        $product_suggestions = Products::orderBy('products.id', 'desc')
        ->leftjoin('users', 'products.user_id', '=', 'users.id')
        ->where('products.id', '!=', $product_id)
        ->where('products.user_id', '!=', Auth::check() ? Auth::user()->id : '')
        ->where('users.body_type', Auth::check() ? Auth::user()->body_type : '')
        ->whereNotIn('products.id', Rent::select('product_id')->where('user_id', Auth::check() ? Auth::user()->id : '')->where('status', '!=', 'Cart')->get())->take(6)->select("products.id")->get();

        if ($product_suggestions->first() == NULL) {
            $product_suggestions = Products::orderBy('products.id', 'desc')
            ->leftjoin('users', 'products.user_id', '=', 'users.id')
            ->where('products.id', '!=', $product_id)
            ->where('products.user_id', '!=', Auth::check() ? Auth::user()->id : '')
            ->whereNotIn('products.id', Rent::select('product_id')
            ->where('user_id', Auth::check() ? Auth::user()->id : '')
            ->where('status', '!=', 'Cart')->get())
            ->select("products.id")
            ->take(6)->get();
        }

        if ($product_detail) {

            $suggestion_product_ids = collect($product_suggestions)->pluck('id')->all();

            $product_suggestions = Products::whereIn('id', $suggestion_product_ids)->with(['added_by' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])->get(['id', 'user_id', 'name', 'price', 'picture']);

            foreach ($product_suggestions as $key => $value) {
                $value->picture = env('APP_URL').'/'.$value->picture;

                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $value->rating = $rating;
                $value->added_by->last_name = substr($value->added_by->last_name, 0, 1);
            }
            $rating = ProductUserReview::where('product_id', $product_id)->avg('rating');
            $rating = round($rating);
            $product_detail->rating = $rating;
            $product_detail->product_suggestions = $product_suggestions;

            $this_item_on_rent = Rent::where('product_id', $product_id)->whereIn('status', ['Pending', 'Accepted', 'Payment Accepted'])->orderBy('id', 'desc')->first();

            if ($this_item_on_rent) {
                $product_detail->unavailable_for_rent_from = date('Y/m/d', strtotime($this_item_on_rent->rental_start_date . "- 3 days"));
                $product_detail->unavailable_for_rent_to = date('Y/m/d', strtotime($this_item_on_rent->rental_end_date . "+ 3 days"));
            } else {
                $product_detail->unavailable_for_rent_from = date('Y/m/d', strtotime("- 3 days"));
                $product_detail->unavailable_for_rent_to = date('Y/m/d', strtotime("- 3 days"));
            }

            $product_detail = $product_detail->toArray();

            $product_detail['measurement_image'] = env('APP_URL').'/'.'user-interface/img/size_chart.png';
            $product_detail['added_by']['body_type_image'] = env('APP_URL').'/'."user-interface/img/body-type-new-" . $product_detail['added_by']['body_type'] . ".png";

            $product_detail['is_rented'] = $isRented;
            $response['product_detail'] = $product_detail;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Product detail.',
                'data'      => $response,
            ], $this->successStatus);
        }

        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product detail not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getProfile(Request $request) {
        $code = SUCCESS;
        $msg = "User profile get successfully.";
        $user = auth()->guard('api')->user();
        $response['user_profile'] = $user;
        return Response::json(['code' => $code, 'msg' => $msg, 'data' => (object) $response]);
    }

    public function getMessages(Request $request) {

        $user = auth()->guard('api')->user();

        $messages = [];
        $extracted_messages = [];

        $rooms = Messages::where('to_user_id', $user->id)->distinct()->select('room_id')->get();
        if (count($rooms)) {
            foreach ($rooms as $value) {
                $messages[] = Messages::where('room_id', $value->room_id)
                ->where('to_user_id', $user->id)
                ->orderBy("id", "desc")
                ->take(1)
                ->get(["id", "room_id", "content"]);
            }

            if (count($messages)) {
                foreach ($messages as $value) {
                    $extracted_messages[] = $value[0];
                }
            }
        }
        if ($extracted_messages) {
            foreach ($extracted_messages as $key => $value) {
                $messages_data = Messages::getData2($value->id, $value->room_id, $user->id);

                $value->sent                         = $messages_data->time_duration;
                $value->first_name                   = $messages_data->users_data->users_information->first_name;
                $value->last_name                    = $messages_data->users_data->users_information->last_name;
                if($messages_data->users_data->users_information->facebook_id) {
                    $value->profile_picture             = $messages_data->users_data->users_information->profile_picture;
                    $value->profile_picture_custom_size = $messages_data->users_data->users_information->profile_picture_custom_size;
                } else if($messages_data->users_data->users_information->twitter_id) {
                    $value->profile_picture             = $messages_data->users_data->users_information->profile_picture;
                    $value->profile_picture_custom_size = $messages_data->users_data->users_information->profile_picture_custom_size;
                } else {
                    $value->profile_picture             = env('APP_URL').'/'.$messages_data->users_data->users_information->profile_picture;
                    $value->profile_picture_custom_size = env('APP_URL').'/'.$messages_data->users_data->users_information->profile_picture_custom_size;
                }
            }
            $response['messages'] = $extracted_messages;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'User messages.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Messages not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function roomMessages(Request $request) {

        $rules = [];
        $rules['room_id'] = 'required|integer|min:1';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }
        
        $user = auth()->guard('api')->user();

        $messages = [];
        // $extracted_messages = [];

        $messages = Messages::where('room_id', $request->room_id)
        ->where('to_user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get();

        if (count($messages) > 0) {
            // $messages = collect([$messages])->collapse()->sortBy('created_at');

            // foreach ($messages as $key => $value) {
            //     $message_data = Messages::getData($value->id);
            //     $extracted_messages[$key] = $message_data;
            //     // dd($extracted_messages);
            // }
            $response['messages'] = $messages;
            // $response['messages_detail'] = $extracted_messages;
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Messages found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Messages not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getCleanerList(Request $request) {

        $user = auth()->guard('api')->user();
        $response = [];

        $cleaner_list = Cleaner::orderBy('id', 'desc')->get();

        if (count($cleaner_list) > 0) {
            $response['cleaners_list'] = $cleaner_list;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Cleaners list.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Cleaners not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getConfig(Request $request) {
        $configuration = Configuration::find(1);
        $code =  SUCCESS;
        $msg = "Configurations."; 
        $response = [];
        unset($configuration->commision);
        unset($configuration->id);
        unset($configuration->paypal_account);
        unset($configuration->created_at);
        unset($configuration->updated_at);
        $configuration->social_media_links = unserialize ($configuration->social_media_links);
        $response['configuration'] = $configuration;

        return Response::json(['code' => $code, 'msg' => $msg, 'data' => (object) $response]);
    }

    public function per($request){
        switch ($request->per) {
            case 'per_day':
                $this->data['per'] = 1;
                break;
            case 'per_week':
                $this->data['per'] = 7;
                break;
            case 'per_month':
                $this->data['per'] = 30;
                break;
            default:
                $this->data['per'] = 1;
                break;
        }
    }
}
