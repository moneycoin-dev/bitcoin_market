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
}
