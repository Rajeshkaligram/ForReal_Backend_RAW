<?php

namespace App\Http\Controllers\UserInterface;

use App\Services\Payments\PayPalAdaptiveService;
use App\models\Rent\RentTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Input;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Helper;
use Carbon\Carbon;

use App\Models\Validators;
use App\Models\Notification\Notification;
use App\Models\Wishlist\Wishlist;
use App\Models\Categories;
use App\Models\Cart\Cart;
use App\User;
use App\Models\Rent\Rent;
use App\Models\Products\Products;
use App\Models\Pages\PageContent;
use App\Models\DeviceToken;

use Auth;
use Crypt,Session;
use Illuminate\Support\Facades\Mail;
use Srmklive\PayPal\Services\ExpressCheckout;

class CartController extends Controller{

    function getIndex(Request $request)
    {

        $this->data['body_type']    =   '';
        $this->data['size']         =   '';
        $this->data['price']        =   Products::max('price');
        $this->data['per']          =   1;
        $this->data['location']     =   '';
        $this->data['height']       =   '';
        $this->data['season']       =   '';
        $this->data['category']     =   '';

        if ($request->has('body_type'))     { $this->data['body_type']  = $request->body_type;  }
        if ($request->has('size'))          { $this->data['size']       = $request->size;       }
        if ($request->has('price'))         { $this->data['price']      = $request->price;  }
        if ($request->has('per'))           { $this->per($request);                             }
        if ($request->has('location'))      { $this->data['location']   = $request->location;   }
        if ($request->has('height'))        { $this->data['height']     = $request->height;     }
        if ($request->has('season'))        { $this->data['season']     = $request->season;     }
        if ($request->has('category'))      { $this->data['category']   = $request->category;   }

        $this->data['budget']     = $this->data['price'] / $this->data['per'];
        $this->data['categories'] = Categories::where('status', 1)->get();

        $this->data['cart'] = Rent::groupBy('rent_details.id','products.id')
                                            ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
                                            ->leftjoin('product_categories','products.id','=','product_categories.product_id')
                                            ->leftjoin('categories','product_categories.category_id','=','categories.id')
                                            ->leftjoin('users','products.user_id','=','users.id')
                                            ->where('rent_details.user_id', '=', Auth::user()->id )
//                                            ->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
//                                            ->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
//                                            ->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
//                                            ->where('products.price', '<=', $this->data['budget'])
//                                            ->whereNotIn('rent_details.id', Rent::select('id')->where('user_id', Auth::user()->id)->where('status', '!=', 'Cart')->get())
//                                            ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
//                                            ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
//                                            ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
                                            ->where('rent_details.status', 'Cart')
                                            ->select('rent_details.id as cartID',
                                                     'products.id as productID')
                                            ->paginate(3);                                        

        $this->data['product_owner'] = Rent::groupBy('users.id')
                                            ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
                                            ->leftjoin('product_categories','products.id','=','product_categories.product_id')
                                            ->leftjoin('categories','product_categories.category_id','=','categories.id')
                                            ->leftjoin('users','products.user_id','=','users.id')
                                            ->where('rent_details.user_id', '=', Auth::user()->id )
//                                            ->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
//                                            ->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
//                                            ->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
//                                            ->where('products.price', '<=', $this->data['budget'])
//                                            ->whereNotIn('rent_details.id', Rent::select('id')->where('user_id', Auth::user()->id)->where('status', '!=', 'Cart')->get())
//                                            ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
//                                            ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
//                                            ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
                                            ->where('rent_details.status', 'Cart')
                                            ->select('users.id as userID')
                                            ->get();

        $this->data['page_content'] = PageContent::getData('none',10);

        return view('user-interface.cart.index', $this->data);
    }


    function displayCart($id) {

        $productId = Rent::find($id)->product_id;

        $data['cartID'] = $id;
        $data['productID'] = $productId;

        return view('user-interface.cart.display-cart', $data);
    }



    function per($request){
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

    function getAddToCart(Request $request)
    {
//        if($request->delivery_option == 'Ups'){
//            Helper::flashMessage('',$request->delivery_option,'success');
//            return response()->json(["result" => 'success']);
//        }
//        else{
        $validator = Validators::frontendValidate($request, "rent");
        if ($validator === true) {
            //print_r($request->all());exit;
            $product_details = Products::where('id', $request->productID)->first();
            $date1           = date_create($request->start_date);
            $date2           = date_create($request->end_date);

            if(!$date1 || !$date2) {
                return response()->json(["result" => 'failed', "errors" => ['Date' => ['Re-Fill rental period days']]]);
            }

            $diff            = date_diff($date1, $date2);
            $total_days      = $diff->format("%a");
            $total_days      += 1;
            $total           = ($total_days * $product_details->price);
            $request->total  = $total;
            Rent::addData($request);
            Helper::flashMessage('Great!', 'You have added an item to your Cart.', 'success');
            return response()->json(["result" => 'success']);
        }
        return response()->json(["result" => 'failed', "errors" => $validator->errors()->messages()]);
//        }
        
        
    }

    function changeDelivery (Request $req) {
        $opt = $req->input('value');
        $id = $req->input('id');

        $rentDetail = Rent::where('id', $id)->update(['delivery_option' => $opt]);
        Helper::flashMessage('Great!', 'You have Successfully Changed the Delivery Option.', 'success');
        return response()->json(["result" => 'success']);
    }

    function getDeleteToCart(Request $request)
    {
        /*$cart_data = Cart::where('product_id', Crypt::decrypt($request->product_id))->where('user_id',  Auth::user()->id)->first();
        $data = $cart_data->id;
        Cart::deleteData($data);

        return response()->json([ "result"  => 'success' ]);*/
        
        $cart_data = Rent::where('product_id', Crypt::decrypt($request->product_id))->where('user_id',  Auth::user()->id)->first();
        $data = $cart_data->id;
        Rent::deleteData($data);

        return response()->json([ "result"  => 'success' ]);
    }

    function getCheckout(Request $request)
    {        
    	//Session::put('cart_request',$request);
        $this->data['body_type']    =   '';
        $this->data['size']         =   '';
        $this->data['budget']           =   Products::max('price');
        $this->data['location']     =   '';
        $this->data['height']       =   '';
        $this->data['season']       =   '';
        $this->data['category']     =   '';

        if ($request->has('body_type'))     { $this->data['body_type']  = $request->body_type;  }
        if ($request->has('size'))          { $this->data['size']       = $request->size;       }
        if ($request->has('budget'))        { $this->data['budget']     = $request->budget;     }
        if ($request->has('location'))      { $this->data['location']   = $request->location;   }
        if ($request->has('height'))        { $this->data['height']     = $request->height;     }
        if ($request->has('season'))        { $this->data['season']     = $request->season;     }
        if ($request->has('category'))      { $this->data['category']   = $request->category;   }

        
        $rent_data = Rent::groupBy('rent_details.id','products.id')
                                            ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
                                            ->leftjoin('product_categories','products.id','=','product_categories.product_id')
                                            ->leftjoin('categories','product_categories.category_id','=','categories.id')
                                            ->leftjoin('users','products.user_id','=','users.id')
                                            ->where('rent_details.user_id', '=', Auth::user()->id )
                                            ->where('rent_details.status','Cart')
                                            ->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
                                            ->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
                                            ->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
//                                            ->where('products.price', '<=', $this->data['budget'])
                                            ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
                                            ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
                                            ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
                                            ->select('rent_details.id as rentID',
                                                     'products.id as productID')
                                            ->get(); 
         //echo "<pre>";
         //print_r($rent_data);        exit;                                                    
        /*foreach($rent_data as $value) {
            if(count(Products::getData($value->productID)->availability) != 0){
                Helper::flashMessage('Failed!','There are items unavailable for rental.','error');
                return back();
            }
        }*/

	//Helper::flashMessage('Failed!','Merchant not added paypal account. Please contact to admin.','error');
            	
                //return back();
	$cart_total = $request->total;
	//Session::put('cart_total',$cart_total);
	
        foreach($rent_data as $value) {
            
            /*Rent::manageData($value->rentID, 'Pending');
            Helper::flashMessage('Great!','Items are now pending for approval.','success');

            $product_data = Products::where('id', $value->productID)->first();
            $rent_data = Rent::orderby('id', 'desc')->first();   
            Notification::addData($product_data->user_id, Auth::user()->id, $rent_data->id, 'Rented your item', 'Your items are now pending for approval.', 'rental_request');
            Notification::addData(Auth::user()->id,$product_data->user_id, $rent_data->id, 'One new item rented', 'One new item rented', 'rental_request_sent');*/
            
            $product_data = Products::where('id', $value->productID)->first();
            $user_data = User::where('id',$product_data->user_id)->first();
            //echo $cart_total;
            //print_r($user_data);
            
            if($user_data->paypal_email_address=='') {
            	Helper::flashMessage('Failed!','Merchant not added paypal account. Please contact to admin.','error');
            	
                return redirect()->to('my-cart');
            } else {
                $admin_amount = (float) $cart_total;
                $merchant_amount = ($admin_amount * 90) / 100;
                $cancel_url = url('my-cart/cancel');
                $success_url = url('my-cart/success');
                $result = app(PayPalAdaptiveService::class)->createAdaptivePayKey(
                    $admin_amount,
                    $merchant_amount,
                    $user_data->paypal_email_address,
                    $success_url,
                    $cancel_url,
                    false
                );
                if (!$result['success'] || !$result['pay_key']) {
                    Helper::flashMessage('Failed!', $result['message'], 'error');
                    return redirect()->to('my-cart');
                }
                Rent::where('id',$value->rentID)->update(['cart_total' => $cart_total, 'pay_key' => $result['pay_key']]);
                return redirect()->to('https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_ap-payment&paykey='.$result['pay_key']);
            }
            
        }
        //return back();
        
        
    }
    
    public function payment_success() {
    	$request = Session::get('cart_request');
    	
    	$this->data['body_type']    =   '';
        $this->data['size']         =   '';
        $this->data['budget']           =   Products::max('price');
        $this->data['location']     =   '';
        $this->data['height']       =   '';
        $this->data['season']       =   '';
        $this->data['category']     =   '';

        /*if ($request->has('body_type'))     { $this->data['body_type']  = $request->body_type;  }
        if ($request->has('size'))          { $this->data['size']       = $request->size;       }
        if ($request->has('budget'))        { $this->data['budget']     = $request->budget;     }
        if ($request->has('location'))      { $this->data['location']   = $request->location;   }
        if ($request->has('height'))        { $this->data['height']     = $request->height;     }
        if ($request->has('season'))        { $this->data['season']     = $request->season;     }
        if ($request->has('category'))      { $this->data['category']   = $request->category;   }

        */
        $rent_data = Rent::groupBy('rent_details.id','products.id')
                                            ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
                                            ->leftjoin('product_categories','products.id','=','product_categories.product_id')
                                            ->leftjoin('categories','product_categories.category_id','=','categories.id')
                                            ->leftjoin('users','products.user_id','=','users.id')
                                            ->where('rent_details.user_id', '=', Auth::user()->id )
                                            ->where('rent_details.status','Cart')
                                            ->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
                                            ->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
                                            ->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
//                                            ->where('products.price', '<=', $this->data['budget'])
                                            ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
                                            ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
                                            ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
                                            ->select('rent_details.id as rentID',
                                                     'products.id as productID')
                                            ->get(); 
       foreach($rent_data as $value) {
            if(count(Products::getData($value->productID)->availability) != 0){
                Helper::flashMessage('Failed!','There are items unavailable for rental.','error');
               	return redirect()->to('my-cart');
            }
        }
        
        foreach($rent_data as $value) {
        //$payKey = Session::get('payKey');
    	//$cart_total = Session::get('cart_total');
            //echo $payKey;exit;
            Rent::manageData($value->rentID, 'Pending');
            Helper::flashMessage('Great!','Payment Successfully and Items are now pending for approval.','success');

            $product_data = Products::where('id', $value->productID)->first();
            $rent_data = Rent::orderby('id', 'desc')->first();   
            Notification::addData($product_data->user_id, Auth::user()->id, $rent_data->id, 'Rented your item', 'Your items are now pending for approval.', 'rental_request');
            //Notification::addData(Auth::user()->id,$product_data->user_id, $rent_data->id, 'One new item rented', 'One new item rented', 'rental_request_sent');
  
  	}
        return redirect()->to('my-cart');
         
    }
    
    public function payment_cancel() {
    	Helper::flashMessage('Failed!','Payment Cancel...','error');
                		return redirect()->to('my-cart');
    }
    
    function getMakeOrder(Request $request)
    {

    	$msg = "";
        $this->data['body_type']    =   '';
        $this->data['size']         =   '';
        $this->data['budget']           =   Products::max('price');
        $this->data['location']     =   '';
        $this->data['height']       =   '';
        $this->data['season']       =   '';
        $this->data['category']     =   '';

        if ($request->has('body_type'))     { $this->data['body_type']  = $request->body_type;  }
        if ($request->has('size'))          { $this->data['size']       = $request->size;       }
        if ($request->has('budget'))        { $this->data['budget']     = $request->budget;     }
        if ($request->has('location'))      { $this->data['location']   = $request->location;   }
        if ($request->has('height'))        { $this->data['height']     = $request->height;     }
        if ($request->has('season'))        { $this->data['season']     = $request->season;     }
        if ($request->has('category'))      { $this->data['category']   = $request->category;   }

        
        $rent_data = Rent::groupBy('rent_details.id','products.id')
        ->leftjoin('products', 'rent_details.product_id', '=', 'products.id')
        ->leftjoin('product_categories','products.id','=','product_categories.product_id')
        ->leftjoin('categories','product_categories.category_id','=','categories.id')
        ->leftjoin('users','products.user_id','=','users.id')
        ->where('rent_details.user_id', '=', Auth::user()->id )
        ->where('rent_details.status','Cart')
//        ->where('categories.name', 'LIKE', '%' . $this->data['category'] . '%')
//        ->where('users.body_type', 'LIKE', '%' . $this->data['body_type'] . '%')
//        ->where('products.size', 'LIKE', '%' . $this->data['size'] . '%')
        ->where('products.price', '<=', $this->data['budget'])
//        ->where('users.location', 'LIKE', '%' . $this->data['location'] . '%')
//        ->where('users.height', 'LIKE', '%' . $this->data['height'] . '%')
//        ->where('products.season', 'LIKE', '%' . $this->data['season'] . '%')
        ->select('rent_details.id as rentID','products.id as productID')
        ->get();
        
        if($rent_data) {

            foreach ($rent_data as $value) {
                Rent::manageData($value->rentID, 'Pending');
                $product_data = Products::where('id', $value->productID)->with('user_detail')->first();

                $client = Auth::user()->first_name . " " . Auth::user()->last_name;
                $title  = $client . " Rented your item";

                Notification::addData($product_data->user_id, Auth::user()->id, $value->rentID, "Rented your item", 'New product are now pending for approval.', 'rental_request');

                $email        = $product_data->user_detail->email;
                $merchantName = $product_data->user_detail->first_name . ' ' . $product_data->user_detail->last_name;

                $pictureSpltd = explode(' ', $product_data->picture);
                $picture = implode("%20", $pictureSpltd);

                $data['message']    = "New product are now pending for approval.";
                $data['title']      = $title;
                $data['picture']    = $picture;
                $data['link']       = url('for-rent/booking-list/' . $product_data->id);
                $data['name']       = $merchantName;
                $data['user_type']  = "bp";
                $data['product_id'] = $value->productID;
                $data['rented_id']  = $value->rentID;

                Mail::send('emails.notify', compact('data'), function ($m) use ($email, $title) {
                    $m->to($email)->subject('Rent A Suit - ' . $title);
                    $m->from('info@rentsuit.com');
                });

                $user_device_token = DeviceToken::where('user_id', $product_data->user_id)->get();
                if (count($user_device_token) > 0) {
                    foreach ($user_device_token as $key => $value) {
                        if ($value->device_type == "Android") {
                            $fields = array(
                                'to' => $value->device_token,
                                'data' => array("message" => 'New product are now pending for approval.', 'rental_request', 'title' => $title)
                            );
                            sendPushNotification($fields);
                        }
                    }
                }

                }

            Helper::flashMessage('Great!', 'Order is placed successfully and items are now pending for approval.', 'success');
            //return response()->json(["result" => 'success']);
            return redirect()->to('my-cart');
//            }
	            	
//			Helper::flashMessage('Failed!',$msg,'error');
//        		return redirect()->to('my-cart');
        } 
        
        Helper::flashMessage('Failed!','Cart Empty.','error');
        return response()->json(["result" => 'error']);
    }

    public function getOrderPayment(Request $request) {
        $cartItems = $this->buildCartCheckoutItems();
        if (count($cartItems['items']) === 0) {
            return response()->json(["result" => "failed", "message" => "Cart Empty."]);
        }

        $provider = new ExpressCheckout();
        $checkoutData = [
            'items' => $cartItems['items'],
            'invoice_id' => 'cart-' . Auth::id() . '-' . time(),
            'invoice_description' => 'Rent A Suit cart checkout',
            'return_url' => url('my-cart/pay-order'),
            'cancel_url' => url('my-cart/cancel'),
            'total' => $cartItems['total'],
        ];

        try {
            $response = $provider->setExpressCheckout($checkoutData);
        } catch (\Throwable $e) {
            \Log::error('PayPal checkout init failed: ' . $e->getMessage());
            return response()->json(["result" => "failed", "message" => "Unable to initialize payment."]);
        }

        if (!isset($response['paypal_link'], $response['TOKEN'])) {
            return response()->json(["result" => "failed", "message" => "Unable to initialize payment."]);
        }

        Session::put('checkout_paypal', [
            'token' => $response['TOKEN'],
            'data' => $checkoutData,
            'rent_ids' => $cartItems['rent_ids'],
        ]);

        return response()->json([
            "result" => 'success',
            "url" => $response['paypal_link'],
        ]);
    }

    public function getPayOrder(Request $request) {
        if (!$request->has('token') || !$request->has('PayerID')) {
            Helper::flashMessage('Failed!', 'Payment callback is missing required data.', 'error');
            return redirect()->to('my-cart');
        }

        $checkout = Session::get('checkout_paypal');
        if (!$checkout || ($checkout['token'] ?? '') !== $request->token) {
            Helper::flashMessage('Failed!', 'Payment session expired. Please try checkout again.', 'error');
            return redirect()->to('my-cart');
        }

        $provider = new ExpressCheckout();
        $details = $provider->getExpressCheckoutDetails($request->token);
        $ack = strtoupper((string)($details['ACK'] ?? ''));
        if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            Helper::flashMessage('Failed!', 'Unable to validate payment details.', 'error');
            return redirect()->to('my-cart');
        }

        $response = $provider->doExpressCheckoutPayment(
            $checkout['data'],
            $request->token,
            $request->PayerID
        );

        $paymentAck = strtoupper((string)($response['ACK'] ?? ''));
        $paymentStatus = strtoupper((string)($response['PAYMENTINFO_0_PAYMENTSTATUS'] ?? ''));
        if (!in_array($paymentAck, ['SUCCESS', 'SUCCESSWITHWARNING'], true) || !in_array($paymentStatus, ['COMPLETED', 'PROCESSED', 'PENDING'], true)) {
            Helper::flashMessage('Failed!', 'Payment was not completed.', 'error');
            return redirect()->to('my-cart');
        }

        $payKey = (string)($response['PAYMENTINFO_0_TRANSACTIONID'] ?? $request->token);
        foreach (($checkout['rent_ids'] ?? []) as $rentId) {
            Rent::where('id', $rentId)->where('user_id', Auth::id())->update([
                'pay_key' => $payKey,
                'updated_at' => Carbon::now(),
            ]);
        }

        Session::forget('checkout_paypal');
        return redirect()->to('my-cart/make-order');
    }

    public function getRefund(Request $request) {
        return response()->json([
            "error" => "Legacy PayPal REST refund endpoint removed. Refund must be handled manually or via a supported provider.",
        ], 410);
    }

    private function buildCartCheckoutItems(): array
    {
        $rows = Rent::where('rent_details.user_id', Auth::id())
            ->where('rent_details.status', 'Cart')
            ->join('products', 'rent_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->leftJoin('categories', 'product_categories.category_id', '=', 'categories.id')
            ->select(
                'rent_details.id as rent_id',
                'rent_details.total as rent_total',
                'rent_details.delivery_option',
                'products.name as product_name',
                'products.retail_price',
                'products.cleaning_price',
                'categories.shipping_fee_local',
                'categories.shipping_fee_nationwide'
            )
            ->get();

        $items = [];
        $total = 0.0;
        $rentIds = [];
        foreach ($rows as $row) {
            $shippingCost = 0.0;
            if ($row->delivery_option === 'Regular mail') {
                $shippingCost = (float)($row->shipping_fee_local ?? 0);
            } elseif ($row->delivery_option !== 'Localization') {
                $shippingCost = (float)($row->shipping_fee_nationwide ?? 0);
            }

            $rentTotal = (float)$row->rent_total;
            $fee = $rentTotal * 0.10;
            $lineTotal = round(
                $rentTotal + $fee + (float)$row->retail_price + (float)$row->cleaning_price + $shippingCost,
                2
            );

            $items[] = [
                'name' => substr((string)$row->product_name, 0, 120),
                'price' => $lineTotal,
                'qty' => 1,
            ];

            $total += $lineTotal;
            $rentIds[] = (int)$row->rent_id;
        }

        return [
            'items' => $items,
            'total' => round($total, 2),
            'rent_ids' => $rentIds,
        ];
    }

}
