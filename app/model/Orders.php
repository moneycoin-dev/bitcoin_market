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

    public function getOrders($login, $status ,$pager = NULL, $sales = NULL){
  
       $string = isset($sales) ? 'author' : 'buyer' ;
       
       $q = dibi::select('*')->from('orders')
                ->where(array($string => $login))
                ->where(array('status' => $status));
              
       if ($pager){
           $pager->setItemCount(count($q));
           
           return $q->limit($pager->getLength())
                    ->offset($pager->getOffset())
                    ->fetchAll();       
       } else { 
            return $q->fetchAll();
       }       
    }
    
    public function slcOrdrFild($field, $oid){
        return $this->slc($field, "orders", "order_id", $oid);
    }
    
    public function isOwner($id, $login){  
        $q = $this->slcOrdrFild("author", $id);
        return $this->check($q, $login);
    }
    
    public function getParticipants($orderId){        
        return $this->slc(array("author", "buyer"), "orders", 
                "order_id", $orderId);
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
        $q = $this->slc("order_id", "feedback", "order_id", $id); 
        return isset($q);
    }
    
    public function saveFeedback($feedback){
        $feedback["time"] = time();
        $this->ins("feedback", $feedback);
    }
    
    public function getFeedback($oid){
       return $this->slc("*", "feedback", "order_id", $oid, TRUE);
    }
    
    public function updateFeedback($oid, $feedback){
        $feedback["time"] = time();
        $this->upd("feedback", $feedback, "order_id", $oid);
    }
    
    public function fbInc($oid){
        $this->upd("feedback", array("changed" => +1), "order_id", $oid);
    }
    
    public function getFbChanges($oid){
        return $this->slc("changed", "feedback", "order_id", $oid);
    }
    
    public function getDetails($id){
        return $this->slc("*", "orders", "order_id", $id, TRUE)[0];
    }
    
    public function saveSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getNotesLeft($id, $seller = NULL){
        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';       
        return $this->slc($string, "orders", "order_id", $id);
    }
    
    public function saveDisputeContents($order,$message,$timestamp, $autor){
        dibi::insert('disputes', array('order' => $order, 'message' => $message,
            'timestamp' => $timestamp, 'autor' => $autor))->execute();
    }
    
    public function getDisputeContents($order){        
        return dibi::query("SELECT * FROM [disputes] WHERE `order` = %i ORDER BY "
                . "timestamp ASC", $order);
    }
}