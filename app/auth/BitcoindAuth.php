<?php

namespace App;

class BitcoindAuth {
    
    const credentials = "http://bitcoinrpc:fugenkleber@localhost:18332";
        
    public $btcd;

    public function __construct(){
        
        $this->btcd = new \Nbobtc\Http\Client(self::credentials);
    }
}
