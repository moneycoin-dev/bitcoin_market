<?php

namespace App\Model;

use dibi;

class Wallet extends \DibiRow
{
	
    public function getBtcAddress($login){
        $q = dibi::select('*')->from('users')->where('login = %s', $login)
            ->fetch();

        return $q['btcaddress'];       
    }
    
    public function writeNewBtcAddress($newaddress ,$login, $timestamp){
        dibi::update('users', array('btcaddress' => $newaddress, 'address_request_time' => $timestamp))
                ->where('login = %s', $login)->execute();
    }
    
    public function addressLastRequest($login){
        return dibi::select('address_request_time')->from('users')
                ->where('login = %s', $login)->fetch()['address_request_time'];
    }
}
