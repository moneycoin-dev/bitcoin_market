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
        $q = $this->slect("author", "orders", "order_id", $id);
        return $this->check($q, $login);
    }
    
    public function getOrderParticipants($orderId){        
        return $this->slect(array("author", "buyer"), "orders", 
                "order_id", $orderId);
    }
    
    public function writeOrderToDb(array $arguments){
        dibi::insert('orders', $arguments)->execute();
    }
    
    public function changeOrderStatus($id, $status){     
        $this->upd("orders", array('status' => $status), "order_id", $id);
    }
    
    public function getOrderStatus($id){
        return $this->slect("status", "orders", "order_id", $id);
    }
    
    public function isOrderFinalized($id){        
        $q = $this->slect("finalized", "orders", "order_id", $id);
        return $this->check($q, "yes");
    }
    
    public function orderFinalize($id){
        $this->upd("orders", array('finalized' => 'yes'), "order_id", $id);
    }
    
    public function getOrderDetails($id){
        return $this->slect("*", "orders", "order_id", $id, TRUE)[0];
    }
    
    public function writeSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getNotesLeft($id, $seller = NULL){
        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';       
        return $this->slect($string, "orders", "order_id", $id);
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