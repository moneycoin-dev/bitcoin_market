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
        return $this->check($q, "on");
    }
    
    public function isDosProtectionEnabled(){
        
        $q = $this->valueGetter("dos_protection");
        return $this->check($q, "on");
    }

    public function areWithdrawalsEnabled(){

        $q = $this->valueGetter("withdrawals");
        return $this->check($q, "on");
    }

    public function changeConfig($option, $value){
        $this->upd("config", array("value" => $value), "option", $option);
    }
    
    public function valueGetter($option){
        return $this->slect("value", "config", "option", $option);
    }
    
    public function returnOptions(){
        return dibi::select("*")->from("config")->fetchPairs();
    }
}