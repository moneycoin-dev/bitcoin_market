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
      $this->userData = $this->settings->selectByLogin($id);
    }
    
    public function beforeRender() {
        
      if ($this->request->getUrl()->path == "/profile/"){
          $this->redirect("Dashboard:in");
      }
      $this->template->userData = $this->userData;
    }
}

