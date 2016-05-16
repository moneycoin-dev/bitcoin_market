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

    /** @var Nette\Application\Application */
    protected $app;

    /**
     * Saves app parameter given in
     * services configuration.
     * 
     * @param Nette\Application\Application $app
     */
    public function __construct($app){
        $this->app = $app;
    }

    /*
     * Shortcut function
     * Gets current presenter
     * 
     * @return Nette\Application\UI\Presenter 
     */
    public function pres(){
        return $this->app->getPresenter();
    }
    
    /**
     * Shortcut function
     * Gets current user login
     * 
     * @return string
     */
    public function logn(){   
       	$login = $this->pres()->getUser()
                      ->getIdentity()->login;

        return $login;
    }

    /**
     * Shortcut function
     * Returns session namespace
     * 
     * @param string $section
     * @return Nette\Http\SessionSection
     */
    public function sess($section){
    	return $this->pres()->getSession()->getSection($section);
    }
    
    /**
     * Shortcut function
     * Sets session variable in given namespace
     * 
     * @param string $section
     * @param array $args
     */
    public function sets($section, array $args){
        foreach($args as $key => $value){
            $this->pres()->getSession()->getSection($section)
                ->$key = $value;
        }
    }
    
    /**
     * Common setup for paginators
     * 
     * @param int $page
     * @param int $items
     * @return Paginator
     */
    public function paginatorSetup($page, $items){
        $paginator = new Paginator();
        $paginator->setItemsPerPage($items);
        $paginator->setPage($page);
        
        return $paginator;
    }
    
    /**
     * Common template setup for paginators
     * 
     * @param string $type
     * @param DibiResult $dbData
     * @param int $pgcount
     * @param int $page
     */
    public function paginatorTemplate($type, $dbData, $pgcount, $page){
        $this->pres()->template->$type = TRUE;
        $this->pres()->template->dbData = $dbData; 
        $this->pres()->template->pgCount = $pgcount;                  
        $this->pres()->template->page = $page;
    }
}