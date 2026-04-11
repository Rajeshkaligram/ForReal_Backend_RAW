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
use App\Models\Rent\RentTransactionDetail;
use App\Models\Messages\Messages;
use App\Models\Messages\MessagesRoom;
use App\Models\Notification\Notification;
use App\Models\Validators;
use App\Models\Helper;
use App\Services\Payments\PayPalAdaptiveService;
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;

class RentedController extends ApiBaseController {

    public $successStatus   = 200;
    public $createdStatus   = 201;
    public $notFoundStatus  = 404;
    public $failedStatus    = 422;

    public function getRentedList(Request $request) {
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
        $total  = $request->results_per_page;
        $page   = max(1, (int) $request->page);
        $skip   = ($page - 1) * $total;

        $user = auth()->guard('api')->user();
        $response = [];

        $this->data['sort_index'] = 'rent_details.updated_at';
        $this->data['sort_value'] = 'desc';
        if ($request->has('sort')) {
            $this->sort($request);
        } else {
            $this->data['sort_index'] = 'rent_details.id';
            $this->data['sort_value'] = 'desc';
        }

        $rentedTotal = Rent::where('rent_details.user_id', $user->id)
        ->where('rent_details.status', '!=', 'Cart')
        ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
        ->get();

        $rented_list = Rent::where('rent_details.user_id', $user->id)
        ->where('rent_details.status', '!=', 'Cart')
        ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
        ->select('rent_details.id as clientID', 'products.id as productID', 'rent_details.status as status')
        ->groupBy('rent_details.id', 'rent_details.updated_at', 'rent_details.status', 'products.id', 'products.price', 'products.name')
        ->orderBy($this->data['sort_index'], $this->data['sort_value']);

        $all_rented_products = $rented_list->get()->toArray();

        $rented_list = $rented_list->skip($skip)->take($total)->get();

        if (count($rented_list) > 0) {
            $product_list = [];
            $rented_list = $rented_list->toArray();

            $rented_id = [];
            foreach ($rented_list as $key => $value) {
                array_push($rented_id, $value['clientID']);
            }

            foreach ($rented_id as $key => $value) {
                $rented_list = Rent::where('id', $value)->first();

                $product_detail = Products::where('id', $rented_list->product_id)
                ->with(['added_by' => function($query) {
                    $query->select('id', 'contact_number', 'location', 'body_type', 'first_name', 'last_name', 'profile_picture', 'profile_picture_custom_size');
                }])
                ->first();

                $product_detail['added_by']['profile_picture'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture'];
                $product_detail['added_by']['profile_picture_custom_size'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture_custom_size'];

                $product_detail->picture            = env('APP_URL').'/'.$product_detail->picture;
                $product_detail->rented_id          = $rented_list->id;
                $product_detail->rental_start_date  = $rented_list->rental_start_date;
                $product_detail->rental_end_date    = $rented_list->rental_end_date;
                $product_detail->status             = $rented_list->status;

                $cancellation_flag = "FALSE";

                if ($product_detail->status == "Pending" || $product_detail->status == "Accepted") {
                    $cancellation_flag = "TRUE";
                }
                $product_detail->cancellation_flag = $cancellation_flag;
                $product_list[] = $product_detail;
            }

            foreach ($product_list as $key => $value) {
                if ($user) {
                    $check_on_wishlist_or_not = Wishlist::where('product_id', $value->id)->where('user_id', $user->id)->count();
                    $value->on_wishlist = 0;
                    if ($check_on_wishlist_or_not > 0) {
                        $value->on_wishlist = 1;
                    }
                } else {
                    $value->on_wishlist = 0;
                }
                $rating = ProductUserReview::where('product_id', $value->id)->avg('rating');
                $rating = round($rating);
                $value->rating = $rating;
            }

            $response['rented_list'] = $product_list;
            $response['total'] = count($all_rented_products);
            $response['showing'] = count($product_list);
            $response['total_pages'] = ceil(count($rentedTotal) / $total);

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Rented products found.',
                'data'      => $response,
            ], $this->successStatus);
        }

        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Products not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    function sort($request) {
        switch ($request->sort) {
            case 'date-recently':
            $this->data['sort_index'] = 'rent_details.updated_at';
            $this->data['sort_value'] = 'desc';
            break;
            case 'date-beginning':
            $this->data['sort_index'] = 'rent_details.updated_at';
            $this->data['sort_value'] = 'asc';
            break;
            case 'price-high':
            $this->data['sort_index'] = 'products.price';
            $this->data['sort_value'] = 'desc';
            break;
            case 'price-low':
            $this->data['sort_index'] = 'products.price';
            $this->data['sort_value'] = 'asc';
            break;
            case 'name-asc':
            $this->data['sort_index'] = 'products.name';
            $this->data['sort_value'] = 'asc';
            break;
            case 'name-desc':
            $this->data['sort_index'] = 'products.name';
            $this->data['sort_value'] = 'desc';
            break;
            case 'designer-asc':
            $this->data['sort_index'] = 'designer';
            $this->data['sort_value'] = 'asc';
            break;
            case 'designer-desc':
            $this->data['sort_index'] = 'designer';
            $this->data['sort_value'] = 'desc';
            break;
            default:
            $this->data['sort_index'] = 'rent_details.updated_at';
            $this->data['sort_value'] = 'desc';
            break;
        }
    }

    public function getRentedDetail(Request $request) {

        $response = [];
        $user = auth()->guard('api')->user();

        $rented_product_detail = $rented_list = Rent::where('id', $request->rented_id)
        ->where('status', '!=', 'Cart')
        ->first();

        if ($rented_product_detail) {
            $product_detail = Products::where('id', $rented_product_detail->product_id)
            ->with(['added_by' => function($query) {
                $query->select("id", "contact_number", "location", "body_type", "first_name", "last_name", "profile_picture", "profile_picture_custom_size");
            }])
            ->first();

            $product_detail['picture'] = env('APP_URL').'/'.$product_detail['picture'];

            $product_detail['added_by']['profile_picture'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture'];
            $product_detail['added_by']['profile_picture_custom_size'] = env('APP_URL').'/'.$product_detail['added_by']['profile_picture_custom_size'];

            $cancellation_flag = "FALSE";

            if ($rented_product_detail->status == "Pending" || $rented_product_detail->status == "Accepted") {
                $cancellation_flag = "TRUE";
            }

            $product_detail->cancellation_flag = $cancellation_flag;

            $total_product_review = ProductUserReview::where('product_id', $rented_product_detail->product_id)->count();
            $product_detail->total_reviews = $total_product_review;

            $rating = ProductUserReview::where('product_id', $rented_product_detail->product_id)->avg('rating');
            $rating = round($rating);
            $product_detail->rating = $rating;

            $product_detail->added_by->body_type_image = env('APP_URL')."/user-interface/img/body-type-new-" . $product_detail->added_by->body_type . ".png";

            $check_on_wishlist_or_not = Wishlist::where('product_id', $rented_product_detail->product_id)
            ->where('user_id', $user->id)
            ->count();

            $product_detail->on_wishlist = 0;
            if ($check_on_wishlist_or_not > 0) {
                $product_detail->on_wishlist = 1;
            }
            $rented_product_detail->product_detail = $product_detail;
            $rented_product_detail = $rented_product_detail->toArray();

            $response['rented_product_detail'] = $rented_product_detail;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Rented product detail found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function changeRentedProductStatus(Request $request) {

        $rules = [];
        $rules['rented_id'] = 'required|integer|min:1';
        $rules['status']    = 'required|in:accept,decline,cancel';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

        $rented_id  = $request->rented_id;
        $status     = $request->status;
        $user       = auth()->guard('api')->user();

        $rented_detail = Rent::where('id', $rented_id)->first();
        if ($rented_detail) {
            if ($status == "accept") {
                if ($rented_detail->status == 'Accepted') {
                    return response()->json([
                        'status'    => $this->failedStatus,
                        'message'   => 'Status is already "Accepted".',
                        'data'      => [],
                    ], $this->failedStatus);
                }
                Rent::manageData($rented_id, 'Accepted');
                Notification::addData($rented_detail->user_id, $user->id, $rented_id, 'Accepted your rental request', 'Accepted your rental request', 'accept');
                $user_device_token = DeviceToken::where('user_id', $rented_detail->user_id)->get();
                if (count($user_device_token) > 0) {
                    foreach ($user_device_token as $key => $value) {
                        if ($value->device_type == "Android") {
                            $fields = [
                                'to'    => $value->device_token,
                                'data'  => ["message" => 'Accepted your rental request', 'title' => "Accepted your rental request"]
                            ];
                            sendPushNotification($fields);
                        }
                    }
                }
            } else if ($status == "decline") {
                if ($rented_detail->status == 'Declined') {
                    return response()->json([
                        'status'    => $this->failedStatus,
                        'message'   => 'Status is already "Declined".',
                        'data'      => [],
                    ], $this->failedStatus);
                }
                Rent::manageData($rented_id, 'Declined');
                Notification::addData($rented_detail->user_id, $user->id, $rented_id, 'Declined your rental request', 'Declined your rental request', 'decline');
                $user_device_token = DeviceToken::where('user_id', $rented_detail->user_id)->get();
                if (count($user_device_token) > 0) {
                    foreach ($user_device_token as $key => $value) {
                        if ($value->device_type == "Android") {
                            $fields = [
                                'to'    => $value->device_token,
                                'data'  => ["message" => 'Declined your rental request', 'title' => "Declined your rental request"]
                            ];
                            sendPushNotification($fields);
                        }
                    }
                }
            } else if ($status == "cancel") {
                if ($rented_detail->status == 'Canceled') {
                    return response()->json([
                        'status'    => $this->failedStatus,
                        'message'   => 'Status is already "Canceled".',
                        'data'      => [],
                    ], $this->failedStatus);
                }
                $rented_product_detail = Products::where('id', $rented_detail->product_id)->first();
                Rent::manageData($rented_id, 'Canceled');
                Notification::addData($rented_product_detail->user_id, $user->id, $rented_id, 'Canceled rental request', 'Canceled rental request', 'cancel');
                $user_device_token = DeviceToken::where('user_id', $rented_product_detail->user_id)->get();
                if (count($user_device_token) > 0) {
                    foreach ($user_device_token as $key => $value) {
                        if ($value->device_type == "Android") {
                            $fields = [
                                'to'    => $value->device_token,
                                'data'  => ["message" => 'Canceled rental request', 'title' => "Canceled rental request"]
                            ];
                            sendPushNotification($fields);
                        }
                    }
                }
            }
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Rented product status changed.',
                'data'      => [],
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found in rented products.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function proceedToPayment(Request $request) {
        
        $rules = [];
        $rules['rented_id'] = 'required|integer|min:1';
        $rules['pay_key']   = 'nullable|string|max:120';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors.',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

        $rented_id      = $request->rented_id;
        $pay_key        = (string) ($request->pay_key ?? '');
        $user           = auth()->guard('api')->user();

        $rented_detail  = Rent::where('id', $rented_id)->where('user_id', $user->id)->first();

        if ($rented_detail) {
            if ($rented_detail->status == "Payment Accepted") {
                return response()->json([
                    'status'    => $this->failedStatus,
                    'message'   => 'The payment is already accepted.',
                    'data'      => [],
                ], $this->failedStatus);
            }
            if ($pay_key === '') {
                $pay_key = (string) $rented_detail->pay_key;
            }
            if ($pay_key === '') {
                return response()->json([
                    'status'    => $this->failedStatus,
                    'message'   => 'Payment key is missing.',
                    'data'      => [],
                ], $this->failedStatus);
            }

            $paymentDetails = app(PayPalAdaptiveService::class)->getPaymentDetails($pay_key, true);
            if (!$paymentDetails['success'] || !$paymentDetails['verified']) {
                return response()->json([
                    'status'    => $this->failedStatus,
                    'message'   => $paymentDetails['message'] ?? 'Payment not yet received.',
                    'data'      => [
                        'payment_status' => $paymentDetails['payment_status'] ?? null,
                    ],
                ], $this->failedStatus);
            }

            $transaction                      = new RentTransactionDetail;
            $transaction->rented_detail_id    = $rented_id;
            $transaction->product_id          = $rented_detail->product_id;
            $transaction->user_id             = $user->id;
            $transaction->total_amount        = $rented_detail->total;
            $transaction->pay_key             = $pay_key;
            $transaction->save();

            $rented_product_detail  =   Products::where('id', $rented_detail->product_id)->first();
            $rented_detail->status  =   "Payment Accepted";
            $rented_detail->pay_key = $pay_key;
            $rented_detail->save();

            Notification::addData($rented_product_detail->user_id, $user->id, $rented_id, 'Payment Accepted', 'Payment Accepted', 'payment accepted');

            $user_device_token = DeviceToken::where('user_id', $rented_product_detail->user_id)->get();
            if (count($user_device_token) > 0) {
                foreach ($user_device_token as $key => $value) {
                    if ($value->device_type == "Android") {
                        $fields = [
                            'to'    => $value->device_token,
                            'data'  => ["message" => 'Payment Accepted', 'title' => "Payment Accepted"]
                        ];
                        sendPushNotification($fields);
                    }
                }
            }
            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Payment accepted successfully.',
                'data'      => [],
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Product not found in rented products.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function getTransactionList(Request $request) {

        $response   = [];
        $user       = auth()->guard('api')->user();

        $transaction_list = RentTransactionDetail::where('user_id', $user->id)
        ->with(['rent_details' => function($query) {
            $query->with('product_detail');
        }, 'user_detail' => function($query) {
            $query->select('id', 'first_name', 'last_name', 'email', "profile_picture", "profile_picture_custom_size");
        }])
        ->orderBy('id', 'desc')
        ->get();

        if (count($transaction_list) > 0) {
            $transaction_list = $transaction_list->toArray();

            foreach ($transaction_list as $key => $value) {
                $transaction_list[$key]['rent_details']['product_detail']['picture'] = env('APP_URL').'/'.$value['rent_details']['product_detail']['picture'];
                $transaction_list[$key]['user_detail']['profile_picture'] = env('APP_URL').'/'.$value['user_detail']['profile_picture'];
                $transaction_list[$key]['user_detail']['profile_picture_custom_size'] = env('APP_URL').'/'.$value['user_detail']['profile_picture_custom_size'];
            }

            $response['transactions'] = $transaction_list;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Transactions found.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Transactions not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function transactionDetail(Request $request) {

        $response = [];
        $user = auth()->guard('api')->user();

        $transactions = RentTransactionDetail::where('id', $request->transaction_id)->with(['rent_details' => function($query) {
            $query->with('product_detail');
        }, 'user_detail' => function($query) {
            $query->select('id', 'first_name', 'last_name', 'email', "profile_picture", "profile_picture_custom_size");
        }])->first();

        if ($transactions) {
            $transactions = $transactions->toArray();

            $transactions['rent_details']['product_detail']['picture'] = env('APP_URL').'/'.$transactions['rent_details']['product_detail']['picture'];
            $transactions['user_detail']['profile_picture'] = env('APP_URL').'/'.$transactions['user_detail']['profile_picture'];
            $transactions['user_detail']['profile_picture_custom_size'] = env('APP_URL').'/'.$transactions['user_detail']['profile_picture_custom_size'];

            $response['transaction_detail'] = $transactions;

            return response()->json([
                'status'    => $this->successStatus,
                'message'   => 'Transaction detail.',
                'data'      => $response,
            ], $this->successStatus);
        }
        return response()->json([
            'status'    => $this->notFoundStatus,
            'message'   => 'Transaction not found.',
            'data'      => [],
        ], $this->notFoundStatus);
    }

    public function payRental($rented_id) {
        return Response::json([
            'code' => UNSUCCESS,
            'msg' => 'Legacy PayPal REST checkout has been removed. Use the Adaptive flow endpoint.',
        ]);
    }


    public function payment_success(Request $req, $id) {
        $msg = 'Legacy PayPal REST callback has been removed. Use the Adaptive flow callback.';
        Helper::flashMessage('Error!', $msg, 'error');
        return redirect('/');
    }


    public function payment_cancel($id) {

        Helper::flashMessage('Error!', 'Transaction Canceled.', 'error');
        return redirect('/');

    }


}
