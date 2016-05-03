<?php

namespace App\Model;

use dibi;


class Configuration extends \DibiRow {

    public function changeMarketMode($mode){

        dibi::update("config", array("value" => $mode))
                ->where(array("option" => "maitenance"))->execute();
    }

    public function isMarketInMaintenanceMode(){

        $q = dibi::select("value")->from("config")
                ->where(array("option" => "maitenance"))->fetch()["value"];

        if ($q == "on"){
                return TRUE;
        }

        return FALSE;
    }

    public function changeWithdrawalState($state){

        dibi::update("config", array("value" => $state))
                ->where(array("option" => "withdrawals"))->execute();
    }

    public function areWithdrawalsEnabled(){

        $q = dibi::select("value")->from("config")
                ->where(array("option" => "withdrawals"))->fetch()["value"];

        if ($q == "enabled"){
                return TRUE;
        }

        return FALSE;
    }
    
    public function setEscrowAddress($address){
        
        dibi::update("config", array("value" => $address))
                ->where(array("option" => "escrow_address"))->execute();
    }
}