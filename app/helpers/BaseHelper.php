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

    public function sugar(){
            return $this->app->app->getPresenter();
    }

    public function logn(){   

       	$login = $this->sugar()->getUser()
                      ->getIdentity()->login;

        return $login;
    }

    public function sess($section){
    	return $this->sugar()->getSession()->getSection($section);
    }
    
    public function sets($section, array $args){
        foreach($args as $key => $value){
            $this->sugar()->getSession()->getSection($section)
                ->$key = $value;
        }
    }
}