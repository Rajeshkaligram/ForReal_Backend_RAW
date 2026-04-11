<?php

namespace App\Functions;

use App\Models\Configuration;

class PaypalConfig {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */


    public static function getApiContext() {
        throw new \RuntimeException('Legacy PayPal REST SDK flow removed. Migrate this flow to PayPalAdaptiveService or Srmklive PayPal API.');
    }


}
