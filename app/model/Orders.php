<?php

namespace App\Model;

use dibi;

class Orders extends \DibiRow {

    public function getUserOrders($id){
        return dibi::select('*')->from('orders')->where('id = %i', $id)->fetchAll();
    }
    
    public function isOwner($id, $userid){
        $q =  dibi::select('id')->from('orders')->where('id = %i', $id)->fetch();
        
        if ($q == $userid){
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
                ->where('id = %i', $id)->execute();
    } 
}