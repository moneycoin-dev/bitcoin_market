<?php

namespace App\Model;

use dibi;
use Nette\Utils\DateTime;

/**
 * 
 * @what Orders data model class
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Orders extends BaseModel {

    /**
     * Get orders or sales from database
     * Can return combined result with different
     * sale/order statuses
     * 
     * @param string $login
     * @param mixed string|array $status
     * @param Nette\Utils\Paginator $pager
     * @param bool $sales
     * @return DibiResult
     */
    public function getOrders($login, $status ,$pager = NULL, $sales = NULL){ 
       $string = $sales ? "author" : "buyer" ;
       $status = is_array($status) ? $status : array($status);
   
        $q = dibi::select("*")->from("orders")
                ->where(array($string => $login));
  
        $w = "status = ";
        $where = "";
        
        //controls mixed arguments for
        //sales rendering
        for ($i=0; $i<count($status); $i++){
            
            //for right count of ORs in query
            if ($i !== 0){
                $where .= " OR ";    
            }
            
            if ($status[$i] == "Shipped"){
                $where .= "shipped = 'yes' ";
            } 

            else if ($status[$i] == "pending"){
                $where .= "shipped = 'no' ";
            }

            else {
                 $where .= $w."'".$status[$i]."'";
            }
        }
        
        $where = "(". $where . ")";
        $q = $q->where($where);
           
        call_user_func(array($q, "where"), $where);
           
        $q = $q->orderBy("date_ordered DESC");
        
        $status ? $q = $q->orderBy("status DESC") : NULL;

        if ($pager){
            return $this->pgFetch($q, $pager);  
        } else { 
             return $q->fetchAll();
        }     
    }
    
    /**
     * Returns total sums of user spendings
     * or vendor sales
     * 
     * @param string $login
     * @param string $crncy
     * @param string $status
     * @param bool $sales
     * @return DibiResult
     */
    public function getTotals($login,$crncy,$status,$sales=NULL){
        $type = $sales ? "author" : "buyer";
        $what = $crncy == "czk" ? "SUM(czk_price)" : "SUM(final_price)";
        $arg = array($type => $login);
        $status ? $arg["status"] = $status : TRUE;
        return $this->slc($what, "orders", $arg);
    }
    
    /**
     * Helper function for selection 
     * result field for patricular order
     * 
     * @param string $field
     * @param int $oid
     * @return DibiResult
     */
    public function slcOrdrFild($field, $oid){
        return $this->slc($field, "orders", array("order_id" => $oid));
    }
    
    /**
     * Checks if vendor is owner of
     * the listing
     * 
     * @param int $id
     * @param string $login
     * @return bool
     */
    public function isOwner($id, $login){  
        $q = $this->slcOrdrFild("author", $id);
        return $this->check($q, $login);
    }
    
    /**
     * Returns order participants as array
     * @param int $orderId
     * @return DibiResult
     */
    public function getParticipants($orderId){        
        return $this->slc(array("author", "buyer"), "orders", 
                array("order_id" => $orderId));
    }
    
    /**
     * Saves order data into database
     * @param array $arguments
     * @return int last insert ID
     */
    public function saveToDB(array $arguments){
        return $this->ins("orders", $arguments, TRUE);
    }
    
    /**
     * Changes Order's actual status 
     * (pending, closed, dispute)
     * 
     * @param int $id order id
     * @param string $status
     */
    public function changeStatus($id, $status){     
        $this->upd("orders", array("status" => $status), array("order_id" => $id));
    }
    
    /**
     * Changes Order's shipped state
     * @param int $id order id
     */
    public function setShipped($id){
       $this->upd("orders", array("shipped" => "yes"), array("order_id" => $id));
    }
    
    /**
     * Queries order if it is shipped
     * @param int $id order id
     * @return bool
     */
    public function isShipped($id){
        $q = $this->slcOrdrFild("shipped", $id);
        return $this->check($q, "yes");
    }
    
    /**
     * Checks if order is Finalize Early
     * @param int $id order id
     * @return bool
     */
    public function isFe($id){
         $q = $this->slcOrdrFild("FE", $id);
        return $this->check($q, "yes");
    }
    
    /**
     * Check order status against one 
     * received in parameter
     * 
     * @param int $id order id
     * @param string $status
     * @return bool
     */
    public function hasStatus($id, $status){
        $q = $this->slcOrdrFild("status", $id);
        return $this->check($q, $status);
    }
    
    /**
     * Was order already finalized?
     * @param int $id order id
     * @return bool
     */
    public function isFinalized($id){        
        $q = $this->slcOrdrFild("finalized", $id);
        return $this->check($q, "yes");
    }
    
    /**
     * Changes order status to closed
     * and finalizes order
     * @param int $id
     */
    public function finalize($id){
        $this->upd("orders", array("status" => "closed", 
                                   "finalized" => "yes"), array("order_id" => $id));
    }
    
    /**
     * Queries order if user left
     * any feedback for it
     * 
     * @param int $id order id
     * @return bool
     */
    public function hasFeedback($id){
        $q = $this->slc("order_id", "feedback", array("order_id" => $id)); 
        return isset($q);
    }
    
    /**
     * Saves order feedback
     * with time of posting
     * 
     * @param array $feedback
     */
    public function saveFeedback($feedback){
        $feedback["time"] = time();
        $this->ins("feedback", $feedback);
    }
    
    /**
     * Returns feedback for order
     * Used in feedback editing in FE
     * to render form
     * 
     * @param int $oid order id
     * @return DibiResult
     */
    public function getFeedback($oid){
       return $this->slc("*", "feedback", array("order_id" => $oid), TRUE);
    }
    
    /**
     * Actually updates feedback in database
     * @param int $oid
     * @param array $feedback
     */
    public function updateFeedback($oid, $feedback){
        $feedback["time"] = time();
        $this->upd("feedback", $feedback, array("order_id" => $oid));
    }
    
    /**
     * Returns number of feedback edits
     * 1 allowed for users.
     * 
     * @param int $oid order id
     * @return int
     */
    public function getFbChanges($oid){
        return $this->slc("changed", "feedback", array("order_id" => $oid));
    }
    
    /**
     * Return all order parameters as array
     * @param int $id order id
     * @return DibiResult
     */
    public function getDetails($id){
        return $this->slc("*", "orders", array("order_id" => $id), TRUE)[0];
    }
    
    /**
     * Adds seller notes data
     * when seller ships the order
     * 
     * @param int $id order id
     * @param string $notes
     */
    public function saveSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    /**
     * Check if notes was left
     *  ->Contains PGP for buyer notes
     *  ->Arbitary seller notes
     * 
     * @param int $id order id
     * @param bool $seller
     * @return DibiResult
     */
    public function getNotesLeft($id, $seller = NULL){
        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';       
        return $this->slc($string, "orders", array("order_id" => $id));
    }
    
    /**
     * Function that saves dispute discussions
     * @param int $order
     * @param int $timestamp
     * @param string $message
     * @param string $autor
     */
    public function saveDisputeContents($order,$timestamp,$message,$autor){
        dibi::insert('disputes', array('order' => $order, 'message' => $message,
            'timestamp' => $timestamp, 'autor' => $autor))->execute();
    }
    
    /**
     * Returns all dispute messages
     * @param int $order
     * @return DibiResult
     */
    public function getDisputeContents($order){        
        return dibi::select("*")->from("disputes")
                                ->where(array("order" => $order))
                                ->orderBy("timestamp ASC");
    }
    
    /**
     * Increments any int field in any table
     * @param string $table
     * @param string $what
     * @param array $where
     */
    public function incrementor($table, $what, array $where){
         $this->upd($table, array($what => +1), $where);
    }
    
    /**
     * Increments fields related to Users table
     * @param string $what
     * @param string $login
     */
    public function usrIncrementor($what, $login){
        $this->incrementor("users", $what, array("login" => $login));
    }
    
    /**
     * Increments number of feedback editations
     * @param int $oid
     */
    public function fbInc($oid){
        $this->incrementor("feedback", "changed", array("order_id" => $oid));
    }
    
    /**
     * Increments user's level
     * @param string $login
     */
    public function lvlInc($login){
        $this->usrIncrementor("level", $login);
    }
    
    /**
     * Increments user's trust level
     * @param string $login
     */
    public function trustInc($login){
        $this->usrIncrementor("trust", $login);
    }
    
    /**
     * Increments vendor's sales counter
     * @param string $login
     */
    public function saleInc($login){
        $this->usrIncrementor("sales", $login);
    }
    
    /**
     * Increments user's purchases counter
     * @param string $login
     */
    public function purchaseInc($login){
        $this->usrIncrementor("purchases", $login);
    }
    
    /**
     * Sets time when order will
     * automatically finalize and
     * release funds from escrow.
     * 
     * @param int $oid
     * @return int $fTime
     */
    public function setAuFinalizeDate($oid){     
        $dt =  new DateTime();
        $time = time();
        $fTime = $time + ($dt::WEEK * 2);
        
        $this->upd("orders", array("auto_finalize" => $fTime), 
                array("order_id" => $oid));
        
        return $fTime;
    }
    
    /**
     * Change overall and finalized
     * status of order to TRUE.
     * Used when buyer takes no action.
     * 
     * @param int $oid
     */
    public function autoFinalize($oid){
        $this->upd("orders", array("status" => "closed", "finalized" => "yes"),
                array("order_id" => $oid));
    }
}