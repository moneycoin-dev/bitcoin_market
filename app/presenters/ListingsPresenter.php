<?php 

namespace App\Presenters;

use Nette;
use App\BitcoindAuth as BTCAuth;
use App\Model\Listings;
use App\Forms\ListingFormFactory;
use Nbobtc\Command\Command;

class ListingsPresenter extends ProtectedPresenter {
    
    protected $listings;
    
    private function returnLogin(){      
        $login = $this->getUser()->getIdentity()->login;       
        return $login;
    }
    
    private function returnId(){
        $id = $this->getUser()->getIdentity()->getId();       
        return $id;
    }
      
    private function formValidateValues($form){
        
        $values = $form->getValues(TRUE);
        
        foreach ($values as $value){
            if (!isset($value) || (is_string($value) && strcmp($value, "") == 0)){
                $form->addError("Formulář nesmí obsahovat prázdné pole.");
            }
        }
    }
    
    private function imgUpload($images, $form){
        
        foreach($images as $image){
        
            if ($image->isOk() && $image->isImage()){
                $filesPath = getcwd() . "/userfiles/" ;
                $username = $this->returnLogin();
                $userPath = $filesPath . $username;

                //get extension for randomized filename
                $imageName = $image->getName();
                $fileExtension = strrchr($imageName, ".");

                //randomness for file name
                $rand = substr(md5(microtime()),rand(0,26),10);

                if (file_exists($userPath)){
                    $image->move($userPath . "/" . $rand . $fileExtension);

                } else {
                    mkdir($userPath);
                    $image->move($userPath . "/" . $fileExtension);
                }

            } else {
                $form->addError("Vámi uploadovaný soubor není povolen!");
            }
        }     
    }
    
    public function injectBaseModels(Listings $list){
        $this->listings = $list;
    }
    
    protected $formFactory;
    
    public function injectListingForm(ListingFormFactory $factory){
        $this->formFactory = $factory;
    }
    
    public function createComponentListingForm(){
        $form = $this->formFactory->create();
        $form->addSubmit("submit", "Vytvořit");
        $form->onSuccess[] = array($this, 'listingCreate');
        $form->onValidate[] = array($this, 'listingValidate');
       
        return $form;
    }
    
    public function listingCreate($form){
        
        $values = $form->getValues(True);
        $id = $this->getUser()->getIdentity()->login;
        
        $imageLocations = array();
        $images = $form->values['image'];
        
        foreach ($images as $image){
            //get relative path to image for webserver &
            //save relative paths into array
            array_push($imageLocations, substr($image->getTemporaryFile(),
                    strpos($image->getTemporaryFile(), "userfiles/")));     
        }
        
        //serialize img locations to store it in one field in db
        $imgLocSerialized = serialize($imageLocations);
        $this->listings->createListing($id, $values, $imgLocSerialized);
        
        $this->redirect("Listings:in");
    }
    
    public function listingValidate($form){
        
        $this->formValidateValues($form);
        $images = $form->values['image'];
        $this->imgUpload($images, $form);
    }
    
    public function handleVendor(){
        
        $session = $this->getSession()->getSection('balance');
        
        $login =  $this->returnLogin();      
  
        if ($session->balance > 1){
          //  try {
                 $this->listings->becomeVendor($login);
          //  } catch () {
            //    ...
           // }
        } else {
            $this->flashMessage('You dont have sufficient funds!');
        }
    }
    
    public function actionCreateListing(){
        $this->redirect('Listings:create');
    }
    
    public function handleDeleteListing($id){
        $this->listings->deleteListing($id);
    }
    
    public function createComponentEditForm(){
          $frm = $this->formFactory->create();
          $frm->addSubmit("submit", "Upravit");
        
          $frm->onSuccess[] = array($this, 'editSuccess');
          $frm->onValidate[] = array($this, 'editValidate');
        
          return $frm;    
    }
    
    private $actualListingValues;
    private $listingImages;
    private $listingID;
    
    public function actionEditListing($id){
                    
        if ($this->listings->getAuthor($id)['author'] !== $this->returnLogin()){
            $this->redirect("Listings:in");
        } else {
           $this->actualListingValues = $this->listings->getActualListingValues($id);
        }
        
        $listingImages = unserialize($this->listings->getListingImages($id)[0]['product_images']);

        $imgSession = $this->getSession()->getSection('images');
        $imgSession->listingImages = $listingImages;
        $listingSession = $this->getSession()->getSection('listing');
        $listingSession->listingID = $id;
    }

    public function handleDeleteClick($img){

        $listingID = $this->getSession()->getSection('listing')->listingID;

        $images = $this->listings->getListingImages($listingID);

        $session = $this->getSession();
        $images = $session->getSection('images');
        $images->toDelete = $img;
         
        $this->redirect("Listings:alert");     
    }
    
    public function handleDeleteImage(){

       $session = $this->getSession()->getSection('images');
       $img =  $session->toDelete;
       $listingID = $this->getSession()->getSection('listing')->listingID;

       $imgs = unserialize($this->listings->getListingImages($listingID)[0]['product_images']);
       
       unset($imgs[$img]);

       //reindexed array after unset
       $newArray = array();
       
       foreach ($imgs as $image){
           array_push($newArray, $image);
       }
       
       unset($imgs);
       
       //final array - updated - without deleted images to store in db
       $images = serialize($newArray);
       
       $this->listings->updateListingImages($listingID, $images);
       $this->redirect("Listings:editListing", $listingID);
    }
    
    public function handleDeleteAbort(){
        $listingID = $this->getSession()->getSection('listing')->listingID;
        $this->redirect("Listings:editListing", $listingID);
    }
    
    public function renderAlert(){
        
    }
    
    public $id;
    
    public function editSuccess($form){
        $id = $this->actualListingValues[0]['id'];
        $listingID = $this->getSession()->getSection('listing')->listingID;
        $values = $form->getValues();
        
        $form_images = $form->values['image'];
        $imageLocations = array();
        
        foreach ($form_images as $image){
        //get relative path to image for webserver &
        //save relative paths into array
            array_push($imageLocations, substr($image->getTemporaryFile(),
                    strpos($image->getTemporaryFile(), "userfiles/")));     
        }
       
        $existingImages = unserialize($this->listings->getListingImages($listingID)[0]['product_images']);
        $new_images = serialize(array_merge($imageLocations, $existingImages));
        
        $this->listings->updateListingImages($listingID, $new_images);    
        $this->listings->editListing($id, $values);
        $this->flashMessage("Listing uspesne upraven!");
        $this->redirect("Listings:editListing", $listingID);
    }
    
    public function editValidate($form){

        $this->formValidateValues($form);
        $images = $form->values['image'];
        $this->imgUpload($images, $form);
    }
    
    public function beforeRender(){
            $login = $this->getUser()->getIdentity()->login;
            $id = $this->returnId();
                         
            //query bitcoind, get response
            $btcauth = new BTCAuth();
            $client = $btcauth->btcd;
            $command = new Command('getbalance', $login);             
            $response = $client->sendCommand($command);
            $result = json_decode($response->getBody()->getContents(), true);
            
            $section =  $this->getSession()->getSection('balance');
            $section->balance = $result['result'];

            //render edit form with actual values from database
            $component_iterator = $this->getComponent("editForm")->getComponents();
            $componenty = iterator_to_array($component_iterator);
            
            if ($this->actualListingValues != null){
                
                foreach ($componenty as $comp){
                    switch($comp->name){
                        case 'product_name':
                            $comp->setValue($this->actualListingValues[0]['product_name']);
                            break;
                        case 'product_desc':
                            $comp->setValue($this->actualListingValues[0]['product_desc']);
                            break;
                        case 'price':
                            $comp->setValue($this->actualListingValues[0]['price']);
                            break;
                        case 'ships_from':
                            $comp->setValue($this->actualListingValues[0]['ships_from']);
                            break;
                        case 'ships_to';
                            $comp->setValue($this->actualListingValues[0]['ships_to']);
                            break;
                        case 'product_type':
                            $comp->setValue($this->actualListingValues[0]['ships_to']);
                            break;        
                    }
                }
                
            } else {

            }
                           
           $this->template->isVendor = $this->listings->isVendor($id);
           $this->template->listings = $this->listings->getListings($login);
           $this->template->listingImages = $this->getSession()->getSection('images')->listingImages;
           $this->template->listingID = $this->getSession()->getSection('listing')->listingID;
           $this->template->currentUser = $this->returnLogin();          
    }
}
