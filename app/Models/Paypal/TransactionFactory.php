<?php

namespace App\Models\Paypal;

use App\Models\Categories;
use App\Models\ExchangeRate;
use App\Models\Products\Products;
use App\models\Rent\Rent;

class TransactionFactory
{
	/**
	 * @param $basket
	 * @param int $vatRate
	 * @param null $currency
	 * @return array
	 */
    static function fromBasket($basket, $options=[]): array
    {
        $items = [];
        $totalRetailPrice = 0;
        $totalProductPrice = 0;
        $totalFee = 0;
        $totalShippingCost = 0;
        $listRentId = [];
        
        $currency = isset($options['currency']) ? $options['currency'] : CURRENCY_CODE;
        $tax = isset($options['tax']) ? $options['tax'] : 0;
        $insurance = isset($options['insurance']) ? $options['insurance'] : 0;
        $handlingFee = isset($options['handling_fee']) ? $options['handling_fee'] : 0;
        foreach ($basket as $value) {
            $rentInfo = Rent::where('id', ($value->rentID))->first();
            $startDate = date_create($rentInfo->rental_start_date);
            $endDate = date_create($rentInfo->rental_end_date);
            $dayQuantity =  date_diff($startDate, $endDate)->format("%a") + 1;

            $product = Products::where('id', $value->productID)->with('user_detail')->first();
	        
                $product_price = ExchangeRate::toCurrencyRate($currency, $product->price);
                $product_retail_price = ExchangeRate::toCurrencyRate($currency, $product->retail_price);
                $fee = ($product_price * $dayQuantity) / 10;

            $category = Categories::where('id', $product->product_categories[0]->category_id)->first();

            if ($rentInfo->delivery_option === "Localization") {
                $shippingCost = 0;
            } else if($rentInfo->delivery_option === "Regular mail") {
                $shippingCost = $category->shipping_fee_local;
            } else {
                $shippingCost = $category->shipping_fee_nationwide;
            }

            /* @var Products $product */
	
            $items[] = [
                'name' => $product->name,
                'quantity' => 1,
                'price' => $product_price * $dayQuantity + $product_retail_price + $fee,
                'currency' => $currency,
                'description' => $product->description,
            ];
            
            $totalRetailPrice += $product_retail_price;
            $totalProductPrice += $product_price * $dayQuantity;
            $totalFee += $fee;
            $totalShippingCost += $shippingCost;
            $listRentId[] = $rentInfo->id;
        }

        $subtotal = $totalRetailPrice + $totalProductPrice + $totalFee;
        $total = $totalRetailPrice
            + $totalProductPrice
            + $totalFee
            + $totalShippingCost
            + $tax
            + $insurance
            + $handlingFee;

        return [
            'items' => $items,
            'description' => 'Renting on rentasuit.ca',
            'amount' => [
                'currency' => $currency,
                'total' => $total,
                'details' => [
                    'fee' => $totalFee,
                    'tax' => $tax,
                    'insurance' => $insurance,
                    'handling_fee' => $handlingFee,
                    'subtotal' => $subtotal,
                    'shipping' => $totalShippingCost,
                ],
            ],
            'custom' => json_encode($listRentId),
        ];
    }
	
	/**
	 * List of valid currencies
	 * @ref https://developer.paypal.com/docs/api/reference/currency-codes/
	 * @return array
	 */
    static function currencies() {
    	return [
    	    'AUD',
	        'BRL',
	        'CAD',
	        'CZK',
	        'DKK',
	        'EUR',
	        'HKD',
	        'HUF',
	        'INR',
	        'ILS',
	        'JPY',
	        'MYR',
	        'MXN',
	        'TWD',
	        'NZD',
	        'NOK',
	        'PHP',
	        'PLN',
	        'GBP',
	        'RUB',
	        'SGD',
	        'SEK',
	        'CHF',
	        'THB',
	        'USD'
	    ];
    }
}
