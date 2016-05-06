<?php

namespace App\Model;

use dibi;

/**
 * 
 * @what Registration data model class
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Registration extends BaseModel
{

    public function createUser($values)
    {     
        dibi::query('INSERT INTO [users]',$values);
    }

    public function assignBtcAdress($login, $address){        
        $this->upd("users",  array('btcaddress' => $address), "login", $login);
    }

    public function checkIfUserExists($login){
        $rslt = $this->slect("login", "users", "login", $login);

        if ($rslt){
            throw new DuplicateNameException('Zadané jméno již v systému existuje!');
        }
    }    
}

class DuplicateNameException extends \Exception
{}
