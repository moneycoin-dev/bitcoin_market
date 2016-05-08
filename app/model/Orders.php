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

    public function getOrders($login, $status ,$paginator = NULL, $sales = NULL){
        
       $ph = ' = %s';  
       $string = isset($sales) ? 'author'. $ph : 'buyer' . $ph;
       
       if (isset($paginator)){
           
            return dibi::select('*')->from('orders')
                ->where($string, $login)
                ->where(array('status' => $status))
                ->limit($paginator->getLength())->offset($paginator->getOffset());

       } else {
           
            return dibi::select('*')->from('orders')
                ->where($string, $login)
                ->where(array('status' => $status))->fetchAll();
       }       
    }
    
    public function isOwner($id, $login){  
        $q = $this->slc("author", "orders", "order_id", $id);
        return $this->check($q, $login);
    }
    
    public function getOrderParticipants($orderId){        
        return $this->slc(array("author", "buyer"), "orders", 
                "order_id", $orderId);
    }
    
    public function writeOrderToDb(array $arguments){
        return $this->ins("orders", $arguments, TRUE);
    }
    
    public function changeOrderStatus($id, $status){     
        $this->upd("orders", array("status" => $status), "order_id", $id);
    }
    
    public function getOrderStatus($id){
        return $this->slc("status", "orders", "order_id", $id);
    }
    
    public function isOrderFinalized($id){        
        $q = $this->slc("finalized", "orders", "order_id", $id);
        return $this->check($q, "yes");
    }
    
    public function orderFinalize($id){
        $this->upd("orders", array('finalized' => 'yes'), "order_id", $id);
    }
    
    public function hasFeedback($id){
        $q = $this->slc("order_id", "feedback", "order_id", $id); 
        return $this->check($q, NULL); //returns false
    }
    
    public function saveFeedback($feedback){
        $feedback["time"] = time();
        $this->ins("feedback", $feedback);
    }
    
    public function getOrderDetails($id){
        return $this->slc("*", "orders", "order_id", $id, TRUE)[0];
    }
    
    public function writeSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getNotesLeft($id, $seller = NULL){
        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';       
        return $this->slc($string, "orders", "order_id", $id);
    }
    
    public function writeDisputeContents($order,$message,$timestamp, $autor){
        dibi::insert('disputes', array('order' => $order, 'message' => $message,
            'timestamp' => $timestamp, 'autor' => $autor))->execute();
    }
    
    public function getDisputeContents($order){        
        return dibi::query("SELECT * FROM [disputes] WHERE `order` = %i ORDER BY "
                . "timestamp ASC", $order);
    }
}