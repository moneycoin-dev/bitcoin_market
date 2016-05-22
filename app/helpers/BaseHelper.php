<?php

namespace App\Helpers;

use Nette;
use Nette\Utils\Paginator;
use Nette\Utils\DateTime;

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
        $type = is_array($type) ? "multi" : $type;
        $this->pres()->template->$type = TRUE;
        $this->pres()->template->dbData = $dbData; 
        $this->pres()->template->pgCount = $pgcount;                  
        $this->pres()->template->page = $page;
    }
    
    /**
     * Strips buyer name in feedback
     * rendering for better privacy.
     * 
     * @param string $name
     * @return string
     */
    public function stripBuyerName($name){
        $name = str_split($name);
        $arr = array(current($name), end($name));
        $str = str_repeat(".", 7);
        return $arr[0] . $str . $arr[1];
    }
    
    /**
     * Creates new CRON job
     * on local system.
     * 
     * @param type $time
     * @param type $func
     */
    public function newCronJob($func, $time, $id = NULL){
        $index = $_SERVER['DOCUMENT_ROOT'] . "/index.php ";
        $action = "Cron:". $func;
        $command = $index . $action . $id ? $id : "";
        
        $dt = new DateTime();
        $d = $d->from($time);
        
        $cmd = "(crontab -l ; echo \"0 * * * * " .$command ."\") 2>&1 "
                . "| grep -v \"no crontab\" | sort | uniq | crontab -";
        
        shell_exec($cmd);
    }
}