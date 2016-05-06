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
    
    public function valSelect($what, $from, $where, $by, $all = NULL){
        
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
            return dibi::select($whatStr)->from($from)->where($where .' = %s', $by)
                    ->fetch()[$what];
        } 
        
        else if (isset($all) || isset($all) && is_array($what)) {    
            return dibi::select($whatStr)->from($from)->where($where .' = %s', $by)
                    ->fetchAll();
        }   
        
        else if (is_array($what)){
             return dibi::select($whatStr)->from($from)->where($where .' = %s', $by)
                    ->fetch();
        }
    }

    public function checker($q, $wanted){
        
        //db result value checker    
        if ($q == $wanted){
            return TRUE;
        }

        return FALSE;
    }
    
    public function updater($what, $news, $where, $by){
        
        //update query shortcut
        dibi::update($what, $news)
            ->where(array($where => $by))->execute();
    }
}