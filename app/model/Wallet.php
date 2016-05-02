<?php

namespace App\Model;

use dibi;
use Nbobtc\Command\Command;

class Wallet extends \DibiRow
{
    protected $btcClient;
    
    public function __construct($bc){
        $this->btcClient = $bc;
    }
    
    private function command($string, $login = NULL){
        
        $command = new Command($string, $login);
        $response = $this->btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true); 
        
        return $result["result"];
    }
	
    public function getBtcAddress($login){
        $q = dibi::select("*")->from("users")->where("login = %s", $login)
            ->fetch();

        return $q["btcaddress"];       
    }
    
    public function writeNewBtcAddress($newaddress ,$login, $timestamp){
        dibi::update("users", array("btcaddress" => $newaddress, "address_request_time" => $timestamp))
                ->where("login = %s", $login)->execute();
    }
    
    public function addressLastRequest($login){
        return dibi::select("address_request_time")->from("users")
                ->where("login = %s", $login)->fetch()["address_request_time"];
    }
    
    public function getBalance($login){
                
        return $this->command("getbalance", $login);
    }
    
    public function generateAddress($login){
        
        return $this->command("getnewaddress", $login);
    }
    
    public function transfer($amount, $address){
        
    }
}
