<?php

namespace App\Model;

use dibi;

class Orders extends \DibiRow {

    public function getOrders($login, $paginator,$status , $sales = NULL){
        
       $ph = ' = %s';  
       $string = isset($sales) ? 'author'. $ph : 'buyer' . $ph;
       
       return dibi::select('*')->from('orders')
                ->where($string, $login)
                ->where(array('status' => $status))
                ->limit($paginator->getLength())->offset($paginator->getOffset());    
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
}