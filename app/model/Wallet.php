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
    
    public function writeNewBtcAddress($newaddress ,$login){
        dibi::update('users', array('btcaddress' => $newaddress))
                ->where('login = %s', $login)->execute();
    }
}
