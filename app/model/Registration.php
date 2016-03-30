<?php

namespace App\Model;

use dibi;

class Registration extends \DibiRow
{
        
        public function createUser($values)
        {     
            dibi::query('INSERT INTO [users]',$values);
        }
        
        public function assignBtcAdress($login, $address){

            dibi::update('users', array('btcaddress' => $address))
                    ->where('login = %s', $login)->execute();
        }
        
        public function checkIfUserExists($login){
            $rslt = dibi::select('login')->from('users')->where('login = %s', $login)->fetch();
            
            if ($rslt['login']){
                throw new DuplicateNameException('Zadané jméno již v systému existuje!');
            }
        }    
}

class DuplicateNameException extends \Exception
{}
