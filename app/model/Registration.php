<?php

namespace App\Model;

use dibi;

class Registration extends \DibiRow
{
        
        public function createUser($values)
        {
            try {           
                 dibi::query('INSERT INTO [users]',$values);
            
            } catch (Dibi\UniqueConstraintViolationException $e) {
                throw new DuplicateNameException('Zadane jmeno je duplicitni, vyberte prosim jine');
            }
        }
        
        public function assignBtcAdress($login, $address){

            dibi::update('users', array('btcaddress' => $address))
                    ->where('login = %s', $login)->execute();
        }
        
	/*
	public function delete()
        {
        return dibi::query('DELETE FROM [users] WHERE [id]=%i', $this->id);
        }
        */       
}

class DuplicateNameException extends \Exception
{}
