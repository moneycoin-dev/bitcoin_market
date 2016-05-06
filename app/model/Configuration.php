<?php

namespace App\Model;

use dibi;

/**
 * 
 * @what Market configuration model class
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Configuration extends BaseModel {
    
    public function isMarketInMaintenanceMode(){
    
        $q = $this->valueGetter("maitenance");
        return $this->checker($q);
    }
    
    public function isDosProtectionEnabled(){
        
        $q = $this->valueGetter("dos_protection");
        return $this->checker($q, "on");
    }

    public function areWithdrawalsEnabled(){

        $q = $this->valueGetter("withdrawals");
        return $this->checker($q, "on");
    }

    public function changeConfig($option, $value){
        
        dibi::update("config", array("value" => $value))
                ->where(array("option" => $option))->execute();
    }
    
    private function valueGetter($option){
        
        $q = dibi::select("value")->from("config")
                ->where(array("option" => $option))->fetch()["value"];
        
        return $q;
    }
}