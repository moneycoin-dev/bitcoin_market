<?php

namespace App\Presenters;

use Nette;
use App\Model\Settings;

/**
 * 
 * @what User's public profile
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class ProfilePresenter extends ProtectedPresenter {
    
    /** @var App\Model\Settings */
    protected $settings;
    
    /** @var DibiResult */
    protected $userData;
    
    /** @var Nette\Http\Request */ 
    protected $request;
    
    /** @var string */
    protected $id;
    
    /**
     * Dependency injection
     * @param Settings $settings
     */
    public function injectSettings(Settings $settings){
        $this->settings = $settings;
    }
    
    /**
     * Constructs presenter and gets
     * user's login from URL accessed
     * 
     * @param Nette\Http\Request $r
     */
    public function __construct(Nette\Http\Request $r){
        parent::__construct();
        
        $this->request = $r;
        $path = $this->request->getUrl()->getPath();
        $id = substr($path, strrpos($path, "/")+1, strlen($path));
        $this->id = $id;
    }

    /**
     * Views user public profile and handles
     * feedback rendering dependent on given 
     * parameters
     * 
     * @param string $id
     * @param int $page
     * @param string $feedback
     */
    public function actionView($id, $page = NULL, $feedback = NULL){
      
      //user tried access without profile
      //name entered
      if ($id == NULL){
          $this->redirect("Dashboard:in");
      }
      
      //Set tmpl variable with profile name
      //to correctly generate links
      $this->template->acc = $this->id;
      
      //We are on all feedbacks page
      if (!isset($feedback)){
          $this->template->all = TRUE;
      }
      
      $this->drawPaginator($page ? $page: 1, $feedback); 
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
     * Gets details of particular profile
     * and sets them as template variable.
     */
    public function beforeRender() {
      //user tried access without profile
      //name entered
      if ($this->request->getUrl()->path == "/profile/"){
         $this->redirect("Dashboard:in");
      }
      
      $id = $this->id;
      $this->template->userData = $this->settings->getUserDetails($id);
    }
    
    /**
     * Draw paginator of given type
     * @param int $page
     * @param string $type
     */
    private function drawPaginator($page, $type = NULL){
        $paginator = $this->hlp->paginatorSetup($page, 10);
        $lgn = $this->id;
        $dbData = $this->settings->getRecentFb($lgn, $paginator, $type);
        $pgcount = $paginator->getPageCount();
        $this->hlp->paginatorTemplate($type, $dbData, $pgcount, $page);
    }
}

