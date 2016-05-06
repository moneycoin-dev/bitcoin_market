<?php

namespace App\Model;

/**
 * 
 * @what Wallet data model class and command wrappper
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Wallet extends BaseModel
{
    protected $btcClient;
    
    public function __construct($bc){
        $this->btcClient = $bc;
    }
    
    public function command($string, $arg = NULL, array $args = NULL){
        
        //we always have at least one argument 
        //daemon operation
        $arguments = array($string);
        
        if (isset($arg)){
            array_push($arguments, $arg);
        }
        
        if (isset($args)){
            foreach($args as $arg){
                array_push($arguments, $arg);
            }
        }
        
        //one argument as string or array of string arguments
        //can be passed to this function to instantiate Nbobtc\Command
        //implemeted using reflection
        
        $reflect  = new \ReflectionClass("Nbobtc\Command\Command");
        $command = $reflect->newInstanceArgs($arguments);
        $response = $this->btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true); 
        
        return $result['result'];
    }
	
    public function getBtcAddress($login){
        return $this->valSelect("btcaddress", "users", "login", $login);    
    }
    
    public function writeNewBtcAddress($newaddress ,$login, $timestamp){        
        $this->updater("users", array("btcaddress" => $newaddress, 
            "address_request_time" => $timestamp), "login", $login);
    }
    
    public function addressLastRequest($login){      
        return $this->valSelect("address_request_time", "users", "login", $login);
    }
    
    public function getBalance($account){      
        return $this->command("getbalance", $account);
    }
    
    public function generateAddress($account){   
        return $this->command("getnewaddress", $account);
    }
    
    public function validateAddress($address){
        return $this->command("validateaddress", $address);
    }
    
    public function getTransaction($txID){
        return $this->command("gettransaction", $txID);
    }
    
    public function moveFunds($fromAccount, $toAccount, $ammount, $comment = NULL){
        return $this->command("move", func_get_args());
    }
    
    public function sendFunds($fromAccount, $btcAddress){
        return $this->command("sendfrom", func_get_args());
    }
}
