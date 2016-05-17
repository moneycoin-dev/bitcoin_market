<?php

namespace App\Model;

use dibi;

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
       
        $q = dibi::select("*")->from("orders")
                ->where(array($string => $login));
               
        $status = is_array($status) ? $status : array($status);
            
        for ($i=0; $i<count($status); $i++){
            if ($i == 0){
                $q = $q->where(array("status" => $status[$i]));
            } else {
                $q = $q->or(array("status" => $status[$i]));
            }
        }
        
        $q = $q->orderBy("status DESC");
                  
       if ($pager){
           $pager->setItemCount(count($q));
           
           return $q->limit($pager->getLength())
                    ->offset($pager->getOffset())
                    ->fetchAll();       
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
    
    public function getParticipants($orderId){        
        return $this->slc(array("author", "buyer"), "orders", 
                array("order_id" => $orderId));
    }
    
    public function saveToDB(array $arguments){
        return $this->ins("orders", $arguments, TRUE);
    }
    
    public function changeStatus($id, $status){     
        $this->upd("orders", array("status" => $status), "order_id", $id);
    }
    
    public function setShipped($id){
       $this->upd("orders", array("shipped" => "yes"), "order_id", $id);
    }
    
    public function isShipped($id){
        $q = $this->slcOrdrFild("shipped", $id);
        return $this->check($q, "yes");
    }
    
    public function isFe($id){
         $q = $this->slcOrdrFild("FE", $id);
        return $this->check($q, "yes");
    }
    
    public function hasStatus($id, $status){
        $q = $this->slcOrdrFild("status", $id);
        return $this->check($q, $status);
    }
    
    public function isFinalized($id){        
        $q = $this->slcOrdrFild("finalized", $id);
        return $this->check($q, "yes");
    }
    
    public function finalize($id){
        $this->upd("orders", array("status" => "closed", 
                                   "finalized" => "yes"), "order_id", $id);
    }
    
    public function hasFeedback($id){
        $q = $this->slc("order_id", "feedback", array("order_id" => $id)); 
        return isset($q);
    }
    
    public function saveFeedback($feedback){
        $feedback["time"] = time();
        $this->ins("feedback", $feedback);
    }
    
    public function getFeedback($oid){
       return $this->slc("*", "feedback", array("order_id" => $oid), TRUE);
    }
    
    public function updateFeedback($oid, $feedback){
        $feedback["time"] = time();
        $this->upd("feedback", $feedback, "order_id", $oid);
    }
    
    public function getFbChanges($oid){
        return $this->slc("changed", "feedback", "order_id", $oid);
    }
    
    public function getDetails($id){
        return $this->slc("*", "orders", array("order_id" => $id), TRUE)[0];
    }
    
    public function saveSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getNotesLeft($id, $seller = NULL){
        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';       
        return $this->slc($string, "orders", array("order_id" => $id));
    }
    
    public function saveDisputeContents($order,$message,$timestamp, $autor){
        dibi::insert('disputes', array('order' => $order, 'message' => $message,
            'timestamp' => $timestamp, 'autor' => $autor))->execute();
    }
    
    public function getDisputeContents($order){        
        return dibi::query("SELECT * FROM [disputes] WHERE `order` = %i ORDER BY "
                . "timestamp ASC", $order);
    }
    
    public function incrementor($table, $what, array $where){
         $this->upd($table, array($what => +1), $where);
    }
    
    public function usrIncrementor($what, $login){
        $this->incrementor("users", $what, array("login" => $login));
    }
    
    public function fbInc($oid){
        $this->incrementor("feedback", "changed", array("order_id" => $oid));
    }
    
    public function lvlInc($login){
        $this->usrIncrementor("level", $login);
    }
    
    public function trustInc($login){
        $this->usrIncrementor("trust", $login);
    }
    
    public function saleInc($login){
        $this->usrIncrementor("sales", $login);
    }
    
    public function purchaseInc($login){
        $this->usrIncrementor("purchases", $login);
    }
}