<?php

namespace App\Model;

use dibi;

class Orders extends \DibiRow {

    public function getUserOrders($login){
        return dibi::select('*')->from('orders')->where('buyer = %s', $login)->fetchAll();
    }
    
    public function isOwner($id, $login){
        $q =  dibi::select('author')->from('orders')->where('order_id = %i', $id)->fetch();
        
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
        return dibi::select('finalized')->from('orders')
                ->where('order_id = %i', $id)->fetch()['finalized'];
    }
    
    public function getOrderDetails($id){
        return dibi::select('*')->from('orders')
                ->where('order_id = %i', $id)->fetchAll();
    }
    
    public function writeSellerNotes($id, $notes){
        dibi::update('orders', array('seller_notes' => $notes))
                ->where('order_id = %i', $id)->execute();
    }
}