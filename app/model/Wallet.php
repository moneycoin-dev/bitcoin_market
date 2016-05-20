<?php

namespace App\Model;

use dibi;
use \Nbobtc\Http\Client;
use App\Services\PriceConverter;

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
    protected $cv;
    
    public function __construct(Client $bc, PriceConverter $cv){
        $this->btcClient = $bc;
        $this->cv = $cv;
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
        return $this->slc("btcaddress", "users", array("login" => $login));    
    }
    
    public function writeNewBtcAddress($newaddress ,$login, $timestamp){        
        $this->upd("users", array("btcaddress" => $newaddress, 
            "address_request_time" => $timestamp), "login", $login);
    }
    
    public function addressLastRequest($login){      
        return $this->slc("address_request_time", "users", array("login" => $login));
    }
       
    public function storeTransaction(array $args){   
        $esc = isset($args["escrow"]) ? $args["escrow"] : FALSE ;
        
        if ($esc == "yes"){
            $args["status"] = "waiting";
        } else {
            $args["status"] = "finished";
        }
 
        $this->ins("transactions", $args);
    }
    
    public function balanceCheck($acc, $finalPrice, $form = NULL){
        $userBalance = $this->getBalance($acc);
        
        if (!($userBalance >= $finalPrice)){
            if (isset($form)){
                $form->addError(
                        "Nemáte dostatečný počet bitcoinů pro zakoupení produktu.");
            }
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    public function changeTransactionState($state, $oid){
        dibi::update("transactions", array("status" => $state))
                ->where(array("order_id" => $oid, "type" => "pay"))
                ->execute();      
    }
    
    public function wasReleased($oid){
        $q = dibi::select("id")->from("transactions")
                   ->where(array("order_id" => $oid, "type" => "prelease"))
                   ->fetch();
                   
        return $q;
    }
    
    private function getEscrowTotal($oid,$price){
        $pr = $this->slc("czk_ammount", "transactions", 
                array("order_id" => $oid, "type" => "prelease"));
        
        if ($pr){       
            return $price - $pr["czk_ammount"]; 
        }

        return $price;
    }
    
    public function getEscrowed_Order($oid, $rcv = NULL){  
        $a = "transactions.czk_ammount,  transactions.order_id";
        $string = $rcv ? $a .", orders.author": $a ;
        
        $q = dibi::select($string)->from("transactions");
        $w = "transactions.order_id = %s AND type='pay'";
        
        $q = $rcv ? $q->join("orders")
                      ->on("transactions.order_id = orders.order_id") 
                      ->where($w, $oid) 
                  : $q->where($w, $oid);
        
        $q = $q->fetch();
        $q["czk_ammount"] = $this->getEscrowTotal($oid, $q["czk_ammount"]);
                
        if ($rcv){
            return $q;
        }
        
        return $q["czk_ammount"];
    }
    
    public function getEscrowed_Vendor($login){
        $t = "transactions";
        $o = "orders";
        
        $string = $o.".order_id, ".$t.".order_id, ".$t.".type, "
                .$t.".czk_ammount";
        
        $q = dibi::select($string)
                ->from($o)
                ->join($t)
                ->on($o.".order_id = ".$t.".order_id")
                ->where($o.".author = %s", $login)
                ->where($t.".type = 'pay'")
                ->where($t.".status = 'waiting'")
                ->fetchAll();
        
        $total = 0;
        
        foreach($q as $record){
            $aSub = $this->getEscrowTotal($record["order_id"], $record["czk_ammount"]);
            $total = $total + $aSub;
        }
 
       return $total;
    }
    
    public function getPercentageOfEscrowed($escrowed, $perc){
        return ($perc / 100) * $escrowed;
    }
    
    private function saver($vars){
        $args = $this->asArg($vars);
        $args["donetime"] = time();

        $this->storeTransaction($args);
    }
    
    public function moveAndStore($type, $origin, $receiver, $ammount,$order_id = NULL, $escrow = NULL)
    {
        $this->moveFunds($origin, $receiver, $ammount); 
        $czk_ammount = $this->cv->getPriceInCZK($ammount);
        $vars = get_defined_vars();
        $this->saver($vars);
    }
    
    public function sendAndStore($type, $origin, $receiver, $ammount){
        $this->sendFunds($origin, $receiver, $ammount);        
        $vars = get_defined_vars();
        $this->saver($vars);
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
    
    public function sendFunds($fromAccount, $btcAddress, $ammount){
        return $this->command("sendfrom", func_get_args());
    }
}
