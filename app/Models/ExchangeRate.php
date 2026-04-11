<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency_code',
        'rate',
    ];
	
	public static function toCurrencyRate($currency, $amountInCad)
	{
		if (!$currency = ExchangeRate::where('currency_code', $currency)->orderBy('created_at', 'desc')->first()) {
			return false;
		}
		
		return $amountInCad * $currency->rate;
	}
}
