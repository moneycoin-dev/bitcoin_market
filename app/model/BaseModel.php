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
    
    /**
     * Dibi database select shortcut function
     * with built in scenarios for passing multiple
     * arguments as arrays.
     * 
     * @param mixed array|string $what
     * @param string $from
     * @param array $where
     * @param bool $all
     * @return DibiResult
     */
    public function slc($what, $from, array $where, $all = NULL){
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
            return dibi::select($whatStr)->from($from)->where($where)
                    ->fetch()[$what];
        } 
        
        else if (isset($all) || isset($all) && is_array($what)) {    
            return dibi::select($whatStr)->from($from)->where($where)
                    ->fetchAll();
        }   
        
        else if (is_array($what)){
             return dibi::select($whatStr)->from($from)->where($where)
                    ->fetch();
        }
    }
    
    /**
     * Checks if result of the query equals
     * to our desired result.
     * 
     * @param mixed $q
     * @param mixed $wanted
     * @return boolean
     */
    public function check($q, $wanted){
        //db result value checker    
        if ($q == $wanted){
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Dibi database update shortcut function
     * 
     * @param string $what
     * @param array $news
     * @param string $where
     * @param string $by
     */
    public function upd($what, $news, $where, $by){
        //update query shortcut
        dibi::update($what, $news)
            ->where(array($where => $by))->execute();
    }
    
    /**
     * Dibi database insert shortcut function
     * Eventually returns last insert id
     * 
     * @param string $where
     * @param array $what
     * @param bool $insId
     * @return int
     */
    public function ins($where, array $what, $insId = NULL){
        dibi::insert($where, $what)->execute();
        
        if (isset($insId)){
            return dibi::getInsertId();
        }
    }
    
    /**
     * Accepts output from get_defined_vars()
     * And returns associated array in format
     * $varname => $varvalue, significantly decreases
     * writting needed to update or insert into db.
     * 
     * @param array $vars
     * @return array
     */
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