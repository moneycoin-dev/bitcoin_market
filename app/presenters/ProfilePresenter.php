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
    
    protected $settings;
    protected $userData;
    protected $request;
    
    public function injectSettings(Settings $settings){
        $this->settings = $settings;
    }
    
    public function __construct(Nette\Http\Request $r){
        $this->request = $r;
    }

    public function actionView($id) {
      
      if ($id == NULL){
          $this->redirect("Dashboard:in");
      }
   
      //$id variable-name due to nette defaults
      $this->userData = $this->settings->getUserDetails($id)[0];
    }
    
    public function beforeRender() {
        
      if ($this->request->getUrl()->path == "/profile/"){
          $this->redirect("Dashboard:in");
      }
     
      $this->template->userData = $this->userData;
    }
    
    private function drawPaginator($page, $type = NULL){
        $paginator = $this->hlp->paginatorSetup($page, 10);
        $lgn = $this->userData->login;
        $dbData = $this->settings->getRecentFb($lgn, $paginator, $type);
        $pgcount = $paginator->getPageCount();
        $this->hlp->paginatorTemplate($type, $dbData, $pgcount, $page);
    }
    
    public function renderView($page = 1){
        $this->drawPaginator($page, "all");
    }
    
    public function renderPositive($page = 1){
        $this->drawPaginator($page, "positive");
    }
    
    public function renderNegative($page = 1){
        $this->drawPaginator($page, "negative");
    }
    
    public function renderNeutral($page = 1){
        $this->drawPaginator($page, "neutral");
    }
}

