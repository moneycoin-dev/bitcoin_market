<?php

namespace App\Helpers;

use Nette;
use Nette\Utils\Paginator;

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
    
    public function paginatorSetup($page, $items){
        $paginator = new Paginator();
        $paginator->setItemsPerPage($items);
        $paginator->setPage($page);
        
        return $paginator;
    }
    
    public function paginatorTemplate($type, $dbData, $pgcount, $page){
        $this->pres()->template->$type = TRUE;
        $this->pres()->template->dbData = $dbData; 
        $this->pres()->template->pgCount = $pgcount;                  
        $this->pres()->template->page = $page;
    }
}