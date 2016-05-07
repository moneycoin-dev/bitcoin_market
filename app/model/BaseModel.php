<?php

namespace App\Model;

use dibi;

/**
 * 
 * @what Extensible data model
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class BaseModel extends \DibiRow {
    
    public function slect($what, $from, $where, $by, $all = NULL){
        
        //db select shortcut function
        
        $whatStr = "";
        
        //multiple columns for select processing
        if (is_array($what)){
            
            $lastEl = end($what);
            
            foreach($what as $w){
                if ($lastEl !== $w){
                    $whatStr = $whatStr . $w . " , ";
                } else {
                    $whatStr = $whatStr . $w;
                }
            }
        } else {
            $whatStr = $what;
        }
        
        if(!isset($all) && !is_array($what)){
            return dibi::select($whatStr)->from($from)->where(array($where => $by))
                    ->fetch()[$what];
        } 
        
        else if (isset($all) || isset($all) && is_array($what)) {    
            return dibi::select($whatStr)->from($from)->where(array($where => $by))
                    ->fetchAll();
        }   
        
        else if (is_array($what)){
             return dibi::select($whatStr)->from($from)->where(array($where => $by))
                    ->fetch();
        }
    }

    public function check($q, $wanted){
        
        //db result value checker    
        if ($q == $wanted){
            return TRUE;
        }

        return FALSE;
    }
    
    public function upd($what, $news, $where, $by){
        
        //update query shortcut
        dibi::update($what, $news)
            ->where(array($where => $by))->execute();
    }
    
    public function nsrt($where, array $what, $insId = NULL){
        
        dibi::insert($where, $what)->execute();
        
        if (isset($insId)){
            return dibi::getInsertId();
        }
    }
    
    public function asArg($vars){
        
        //returs array of associated arguments
        //by variable name
        
        $args = array();
        
        foreach($vars as $var_name => $value) {
            if ($var_name !== "args"){
                $args[$var_name] = $value;
            }
        }
        
        return $args;
    }
}