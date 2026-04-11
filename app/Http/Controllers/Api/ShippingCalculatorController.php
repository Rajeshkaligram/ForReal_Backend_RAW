<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Services\Shipping\UpsRateService;
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
use Auth, Hash, Input, Session, Redirect, Mail, URL, File, Str, Config, DB, Response, View, Validator, Twilio;
use Crypt;

class ShippingCalculatorController extends ApiBaseController {
   
    public $successStatus   = 200;
    public $createdStatus   = 201;
    public $notFoundStatus  = 404;
    public $failedStatus    = 422;

    public function getShippingCalculator(Request $request) {

        $rules = [];

        // $rules['type']                              = 'required|string|min:2';
        $rules['destination_address']               = 'required';
        $rules['destination_city']                  = 'required';
        $rules['destination_state_province_code']   = 'required'; 
        $rules['destination_countries']             = 'required';
        $rules['destination_zipcode']               = 'required';
        $rules['from_address']                      = 'required';
        $rules['from_city']                         = 'required';
        $rules['from_state_province_code']          = 'required';
        $rules['from_countries']                    = 'required';
        $rules['from_zipcode']                      = 'required';
        $rules['length']                            = 'required|numeric';
        $rules['width']                             = 'required|numeric';
        $rules['height']                            = 'required|numeric';
        $rules['weight']                            = 'required|numeric';

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'status'    => $this->failedStatus,
                'message'   => 'Validation Errors',
                'data'      => $validator->errors(),
            ], $this->failedStatus);
        }

        if($request->type == "Localization") {
            return $this->canada_api($request);
        } else {
            return $this->ups_usa($request);
        }
    }

public function canada_api($request) {

    $username = "c7510ed7ea5a4e1a"; 
    $password = "6149022203b1cdd393e8cc";
    $mailedBy = "8609453";

    // REST URL
    $service_url = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';

    $from_zipcode = explode(" ", $request->from_zipcode);
    $from_zipcode_final = "";
    foreach ($from_zipcode as $key => $value) {
        $from_zipcode_final .= $value;
    }

    $destination_zipcode = explode(" ", $request->destination_zipcode);
    $destination_zipcode_final = "";
    foreach ($destination_zipcode as $key => $value) {
        $destination_zipcode_final .= $value;
    }

    $originPostalCode   = $from_zipcode_final; 
    $postalCode         = $destination_zipcode_final;
    $weight             = $request->weight;

$xmlRequest = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
<customer-number>{$mailedBy}</customer-number>
<parcel-characteristics>
<weight>{$weight}</weight>
</parcel-characteristics>
<origin-postal-code>{$originPostalCode}</origin-postal-code>
<destination>
<domestic>
<postal-code>{$postalCode}</postal-code>
</domestic>
</destination>
</mailing-scenario>
XML;

    $curl = curl_init($service_url); // Create REST Request
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    // curl_setopt($curl, CURLOPT_CAINFO, realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/../../../third-party/cert/cacert.pem');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlRequest);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.cpc.ship.rate-v4+xml', 'Accept: application/vnd.cpc.ship.rate-v4+xml'));

    $curl_response = curl_exec($curl); // Execute REST Request

    if(curl_errno($curl)) {
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Errors.',
            'data'      => curl_error($curl),
        ], $this->failedStatus);
    }

    curl_close($curl);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string('<root>'.preg_replace('/<\?xml.*\?>/', '', $curl_response).'</root>');
    if (!$xml) {
        $msg = 'Failed loading XML ';
        $msg .= $curl_response;
        foreach(libxml_get_errors() as $error) {
            $msg .= ' '.$error->message;
        }
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => 'Unsuccess.',
            'data'      => $msg,
        ], $this->failedStatus);
    } else {
        if ($xml->{'price-quotes'} ) {
            $priceQuotes = $xml->{'price-quotes'}->children('http://www.canadapost.ca/ws/ship/rate-v4');
            if ( $priceQuotes->{'price-quote'} ) {
                $array = array();
                $i = 0;
                foreach ( $priceQuotes as $key => $priceQuote ) {
                    $array[$i]['service']   = $priceQuote->{'service-name'}."";
                    $array[$i]['cost']      = $priceQuote->{'price-details'}->{'due'}." CAD";
                    $i++;
                }

                $response['shipping_plans'] = $array;

                return response()->json([
                    'status'    => $this->successStatus,
                    'message'   => 'Success.',
                    'data'      => $response,
                ], $this->successStatus);
            }
        }
        if ($xml->{'messages'} ) {                  
            $messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');     
            foreach ( $messages as $message ) {
                if (strpos($message->description.'', 'origin-postal-code') !== false) {
                    $msg = "Original Zipcode not found.";
                } else if(strpos($message->description.'', 'postal-code') !== false) {
                    $msg = "Destination Zipcode not found.";
                } else
                $msg = $message->description.'';
                return response()->json([
                    'status'    => $this->failedStatus,
                    'message'   => 'Unsuccess.',
                    'data'      => $msg,
                ], $this->failedStatus);
            }
        }
    }
}

public function ups_usa($request) {
    $result = app(UpsRateService::class)->quote($request->all());
    if (!$result['success']) {
        return response()->json([
            'status'    => $this->failedStatus,
            'message'   => $result['message'],
            'data'      => [],
        ], $this->failedStatus);
    }

    return response()->json([
        'status'    => $this->successStatus,
        'message'   => 'Success.',
        'data'      => ['shipping_plans' => $result['shipping_plans'][0]],
    ], $this->successStatus);
}
}
