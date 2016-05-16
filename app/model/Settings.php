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
        return $this->slc($what, "users", array("id" => $id));
    }

    public function getUserDetails($login){
        return $this->slc("*", "users", array("login" => $login), TRUE);
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
            $this->upd("users", array('password' => $hash), "id", $id);
        }
    }

    public function newPin($pinold, $pinnew, $id){

        $q = $this->selectById($id, "pin");

        if ($pinold != $q){
            throw new BadPinException('Zadali jste špatně starý pin');

        } else {
            $this->upd("users", array('pin' => $pinnew), "id", $id);
        }
    }
    
    public function getPin($login){
        return $this->slc("pin", "users", array("login" => $login));
    }

    public function isPgpNull($id){
        $q = $this->selectById($id, "pubkey");
        return $this->check($q, NULL);
    }

    public function newPgpKey($pubkey, $id){                      
        $this->upd("users", array("pubkey" => $pubkey), "id", $id);
    }

    public function jabberID($jabber, $id){
        $this->upd("users", array("jabber" => $jabber), "id", $id);
    }
    
    public function hasFEallowed($login){       
        $q = $this->slc("fe_allowed", "users", array("login" => $login)); 
        return isset($q) ? TRUE : FALSE;
    }
    
    public function allowFE($login){
        $this->upd("users", array("fe_allowed" => "yes"),
                array("login" => $login));
    }
    
    public function getRecentFb($login, $pager, $type = NULL){
        $what = "listings.id, listings.author, feedback.listing_id,"
               ." feedback.feedback_text, feedback.type, feedback.time,"
               ." feedback.order_id, feedback.buyer";
        
        $q = dibi::select($what)
                ->from("listings")
                ->join("feedback")
                ->on("listings.id = feedback.listing_id")
                ->where("listings.author = %s", $login);
        
        $type ? $q = $q->where("feedback.type = %s", $type) : TRUE;

        $pager->setItemCount(count($q));
        
        return $q->limit($pager->getLength())
                 ->offset($pager->getOffset())
                 ->fetchAll();     
    }
}

class BadPinException extends \Exception
{}
