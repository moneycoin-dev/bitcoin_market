<?php

namespace App\Helpers;

use Nette;

/**
 * 
 * @what Extensible Library of shortcuts helpers for Nette Presenters
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class BaseHelper extends Nette\Object {

    protected $app;

    public function __construct($app){
        $this->app = $app;
    }

    public function pres(){
        return $this->app->getPresenter();
    }

    public function logn(){   

       	$login = $this->pres()->getUser()
                      ->getIdentity()->login;

        return $login;
    }

    public function sess($section){
    	return $this->pres()->getSession()->getSection($section);
    }
    
    public function sets($section, array $args){
        foreach($args as $key => $value){
            $this->pres()->getSession()->getSection($section)
                ->$key = $value;
        }
    }
}