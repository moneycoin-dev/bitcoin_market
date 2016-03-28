<?php 

namespace App\Model;

use dibi;
use Nette\Security\Passwords;

class Settings extends \DibiRow
{             
        public function selectById($id){
            
            return dibi::select('*')->from('users')
                    ->where('id = %i', $id)
                    ->fetch();
        }
        
        public function selectByLogin($login){
            
            return dibi::select('*')->from('users')
                    ->where('login = %s', $login)
                    ->fetch();
        }
        
        public function verifyOldPassword($oldpw, $id){
            $row = $this->selectById($id);
            
            if (!Passwords::verify($oldpw, $row['password'])){
                throw new Nette\Security\AuthenticationException(
                        'The old password is incorrect.');              
            }       
            return TRUE;
        }
        
        public function newPassword($oldpw, $newpw, $id){
            
            if ($this->verifyOldPassword($oldpw, $id)) {
                
                $hash = Passwords::hash($newpw);
            
                dibi::update('users', array('password' => $hash))
                    ->where('id = %i', $id)->execute();      
            }
        }
        
        public function newPin($pinold, $pinnew, $id){
            
            $row = $this->selectById($id);
            
            if ($pinold != $row['pin']){
                throw new BadPinException('Zadali jste špatně starý pin');
                
            } else {
                dibi::update('users', array('pin' => $pinnew))
                    ->where('id = %i', $id)->execute();
            }
        }
        
        public function isPgpNull($id){
            $row = $this->selectById($id);
            
            if ($row['pubkey'] == NULL){
                return TRUE;
            }
            
            return FALSE;
        }
        
        public function newPgpKey($pubkey, $id){                      
            dibi::update('users', array('pubkey' => $pubkey))
                    ->where('id = %i', $id)->execute();      
        }
        
        public function jabberID($jabber, $id){
            dibi::update('users', array('jabber' => $jabber))
                    ->where('id = %i', $id)->execute();
        }
}

class BadPinException extends \Exception
{}
