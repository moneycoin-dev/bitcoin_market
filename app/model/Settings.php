<?php 

namespace App\Model;

use dibi;
use Nette\Security\Passwords;

/**
 * 
 * @what Settings data model class
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Settings extends BaseModel
{             
    public function selectById($id, $what){       
        return $this->valSelect($what, "users", "id", $id);
    }

    public function selectByLogin($login){

        return dibi::select('*')->from('users')
                ->where('login = %s', $login)
                ->fetch();
    }

    public function verifyOldPassword($oldpw, $id){
        $q = $this->selectById($id, "password");

        if (!Passwords::verify($oldpw, $q)){
            throw new Nette\Security\AuthenticationException(
                    'The old password is incorrect.');              
        }       
        return TRUE;
    }

    public function newPassword($oldpw, $newpw, $id){

        if ($this->verifyOldPassword($oldpw, $id)) {
            $hash = Passwords::hash($newpw);
            $this->updater("users", array('password' => $hash), "id", $id);
        }
    }

    public function newPin($pinold, $pinnew, $id){

        $q = $this->selectById($id, "pin");

        if ($pinold != $q){
            throw new BadPinException('Zadali jste špatně starý pin');

        } else {
            $this->updater("users", array('pin' => $pinnew), "id", $id);
        }
    }

    public function isPgpNull($id){
        $q = $this->selectById($id, "pubkey");
        return $this->checker($q, NULL);
    }

    public function newPgpKey($pubkey, $id){                      
        $this->updater("users", array("pubkey" => $pubkey), "id", $id);
    }

    public function jabberID($jabber, $id){
        $this->updater("users", array("jabber" => $jabber), "id", $id);
    }
}

class BadPinException extends \Exception
{}
