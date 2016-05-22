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
    /** @var \Nbobtc\Http\Client */
    protected $btcClient;
    
    /** @var App\Services\PriceConverter */
    protected $cv;
    
    /**
     * 
     * @param Client $bc
     * @param PriceConverter $cv
     */
    public function __construct(Client $bc, PriceConverter $cv){
        $this->btcClient = $bc;
        $this->cv = $cv;
    }

    /**
     * Shortcut function arround bitcoind
     * wrapper for sending commands to the 
     * daemon
     * 
     * @param string $string
     * @param array $arg
     * @param array $args
     * @return array response
     */
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
    
    /**
     * Return bitcoin adress for
     * associated account
     * 
     * @param string $login
     * @return string
     */
    public function getBtcAddress($login){
        return $this->slc("btcaddress", "users", array("login" => $login));    
    }
    
    /**
     * Updates database record of
     * user's associated bitcoin address
     * 
     * @param string $newaddress
     * @param string $login
     * @param int $timestamp
     */
    public function writeNewBtcAddress($newaddress ,$login, $timestamp){        
        $this->upd("users", array("btcaddress" => $newaddress, 
            "address_request_time" => $timestamp), array("login" => $login));
    }
    
    /**
     * Checks user's last address
     * request time from db.
     * 
     * @param string $login
     * @return int
     */
    public function addressLastRequest($login){      
        return $this->slc("address_request_time", "users", array("login" => $login));
    }
    
    /**
     * Saves details of finished
     * transaction into database
     * 
     * @param array $args
     */
    public function storeTransaction(array $args){   
        $esc = isset($args["escrow"]) ? $args["escrow"] : FALSE ;
        
        if ($esc == "yes"){
            $args["status"] = "waiting";
        } else {
            $args["status"] = "finished";
        }
 
        $this->ins("transactions", $args);
    }
    
    /**
     * Checks bitcoin account balance
     * Eventually adds error into form
     * if balance is not sufficient.
     * 
     * @param string $acc
     * @param int $finalPrice
     * @param Form $form
     * @return boolean
     */
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
    
    /**
     * Changes transaction state.
     * 
     * @param type $state
     * @param type $oid
     */
    public function changeTransactionState($state, $oid){
        dibi::update("transactions", array("status" => $state))
                ->where(array("order_id" => $oid, "type" => "pay"))
                ->execute();      
    }
    
    /**
     * Checks if some btc was
     * partially released from escrow.
     * 
     * @param int $oid
     * @return bool
     */
    public function wasReleased($oid){
        $q = dibi::select("id")->from("transactions")
                   ->where(array("order_id" => $oid, "type" => "prelease"))
                   ->fetch();
                   
        return $q;
    }
    
    /**
     * Check actual btcs that
     * user has actually stored.
     * Check for p-releases as well.
     * 
     * @param int $oid
     * @param float $price
     * @return float
     */
    private function getEscrowTotal($oid,$price){
        $pr = $this->slc("czk_ammount", "transactions", 
                array("order_id" => $oid, "type" => "prelease"));
        
        if ($pr){       
            return $price - $pr["czk_ammount"]; 
        }

        return $price;
    }
    
    /**
     * Returns actual ammount in escrow
     * for given order.
     * 
     * @param int $oid
     * @param bool $rcv
     * @return array|int
     */
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
    
    /**
     * Return total ammount for
     * all escrowed orders for
     * given vendor.
     * 
     * @param string $login
     * @return int
     */
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
    
    /**
     * Returns % of escrowed ammount.
     * 
     * @param int $escrowed
     * @param int $perc
     * @return float
     */
    public function getPercentageOfEscrowed($escrowed, $perc){
        return ($perc / 100) * $escrowed;
    }
    
    /**
     * Saves transaction data into database
     * @param array $vars
     */
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
