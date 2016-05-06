<?php

namespace App;

/**
 * 
 * @what BitcoinD access storage object
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class BitcoindAuth {
    
    const credentials = "http://bitcoinrpc:fugenkleber@localhost:18332";
        
    public $btcd;

    public function __construct(){
        
        $this->btcd = new \Nbobtc\Http\Client(self::credentials);
    }
}
