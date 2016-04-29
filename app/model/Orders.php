<?php

namespace App\Model;

use dibi;

class Orders extends \DibiRow {

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
        $q = dibi::select('author')->from('orders')->where('order_id = %i', $id)
                ->fetch();
        
        if ($q['author'] == $login){
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function getOrderParticipants($orderId){
        return dibi::select('author, buyer')->from('orders')
                ->where('order_id = %i', $orderId)->fetch();
    }
    
    public function writeOrderToDb(array $arguments){
        dibi::insert('orders', $arguments)->execute();
    }
    
    public function changeOrderStatus($id, $status){
        dibi::update('orders', array('status' => $status))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getOrderStatus($id){
        return dibi::select('status')->from('orders')
                ->where('order_id = %i', $id)->fetch()['status'];
    }
    
    public function isOrderFE($id){
        return dibi::select('FE')->from('orders')
                ->where('order_id = %i', $id)->fetch()['FE'];
    }
    
    public function isOrderFinalized($id){
        $q =  dibi::select('finalized')->from('orders')
                ->where('order_id = %i', $id)->fetch()['finalized'];
        
        if ($q == "yes"){
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function orderFinalize($id){
        dibi::update('orders', array('finalized' => 'yes'))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getOrderDetails($id){
        return dibi::select('*')->from('orders')
                ->where('order_id = %i', $id)->fetch();
    }
    
    public function writeSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
    
    public function getNotesLeft($id, $seller = NULL){

        $string = isset($seller) ? 'seller_notes' : 'buyer_notes';
        
        return dibi::select($string)->from('orders')
              ->where('order_id = %i', $id)->fetch()[$string];    
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