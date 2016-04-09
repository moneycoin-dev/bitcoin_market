<?php 

namespace App\Presenters;

use Nette;
use App\Model\Listings;
use App\Model\Orders;
use App\Forms\ListingFormFactory;
use App\Forms\VendorNotesFactory;
use Nbobtc\Command\Command;

class ListingsPresenter extends ProtectedPresenter {
    
    protected $URL;
    protected $request;
    
    const MAX_POSTAGE_OPTIONS = 5;
  
    public function __construct(Nette\Http\Request $r){
        parent::__construct();
        
        $this->request = $r; 
        $this->URL = $r->getUrl();
    }
    
    protected $listings;
    
    private function returnLogin(){      
        $login = $this->getUser()->getIdentity()->login;       
        return $login;
    }
    
    private function returnId(){
        $id = $this->getUser()->getIdentity()->getId();       
        return $id;
    }
    
    private function returnPostageArray($values, $flag = NULL){
        //create separate array with postage options and postage prices
        //to later store it in db
        $postage = $postagePrice = $result = array();
        
        $findPostage = "postage";
        $findPrice = "pprice";
       
        foreach ($values as $key => $value) {
            
            if ($flag == NULL){
                
                //code for adding postage options
                //upon listing creation
                
                if (strpos($key, $findPostage) !== FALSE){
                    array_push($postage, $value);
                }

                if (strpos($key, $findPrice) !== FALSE){
                    array_push($postagePrice, $value);
                }
            } else {
                
                //code for adding postage options
                //when user wants to edit his listing
                
                if (strpos($key, $findPostage) !== FALSE ){
                    if (strchr($key, "X")){
                        array_push($postage, $value);
                    }
                }

                if (strpos($key, $findPrice) !== FALSE){
                    if (strchr($key, "X")){
                        array_push($postagePrice, $value);
                    }
                }
            }
        }
        
        $result['options'] = $postage;
        $result['prices'] = $postagePrice;
        
        return $result;
    }
    
    private function fillForm($form , $values){
        
        //fills form after submission with previously
        //entered values
        //used when adding postage options
        
        if (!empty($values)){
            
            $iterator = $form->getControls();
            $allControls = iterator_to_array($iterator);

            foreach ($allControls as $indexName => $control){
                foreach ($values as $valueName => $value){
                    if ($indexName == $valueName){             
                        $control->setValue($value);
                    }
                }
            }
        }
    }
    
    private function formValidateValues($form){
        
        $values = $form->getValues(TRUE);
        
        foreach ($values as $value){
            if (!isset($value) || (is_string($value) && strcmp($value, "") == 0)){
                $form->addError("Formulář nesmí obsahovat prázdné pole.");
            }
        }
    }
    
    public function compareComponentName($name){
        
        //component name comparator called from template
        //serves rendering of delete postage action link
        if (strpos($name, "postage") !== FALSE){    
            if (strpos($name, "add_postage") !== FALSE){
                return FALSE;
            }
            return TRUE;
            
        } else {
            return FALSE;
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
    
    protected $orders;
    
    public function injectOrders(Orders $o){
        $this->orders = $o;
    }
    
    protected $formFactory;
    protected $vendorNotes;
    
    public function injectListingForm(ListingFormFactory $factory){
        $this->formFactory = $factory;
    }
    
    public function injectVendorNotes(VendorNotesFactory $vendorNotes){
        $this->vendorNotes = $vendorNotes;
    }
    
    public function createComponentListingForm(){
        
        $form = $this->formFactory->create();
        $form->addSubmit("submit", "Vytvořit");
        $form->addSubmit("add_postage", "Přidat dopravu")->onClick[] = function (){
            
            //set up postage counter
            $session = $this->getSession()->getSection("postage");
            $counter = &$session->counter;
            
            if ($counter <= self::MAX_POSTAGE_OPTIONS){
                $counter++;
            } else {
                $this->flashMessage("Dosáhli jste maxima poštovních možností.");
            }
            
            //get form component and its values after click
            //and set up session with values
            $form = $this->getComponent("listingForm");
            $values = $form->getValues(TRUE);
            $session->values = $values;
             
            //redirect is a must to re-render new form
            $this->redirect("Listings:create");         
        };
        
        $session = $this->getSession()->getSection("postage");
        $counter = $session->counter;
        $values = $session->values;
        
        //additional postage textboxes logic
        if (!is_null($counter)){
            
            $form->addGroup("Postage");
                
            for ($i =0; $i<$counter; $i++){
                $form->addText("postage" .$i, "Doprava");
                $form->addText("pprice" .$i, "Cena");
            }
        }
        
        //fill form with values from session upon creation
        $this->fillForm($form, $values);
     
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');    
        $form->onSuccess[] = array($this, 'listingCreate');
        $form->onValidate[] = array($this, 'listingValidate');
               
        return $form;
    }
    
    public function listingCreate($form){
        
        //do things only if submited by "create button"
        if (!$form['add_postage']->submittedBy){
            
            //things to do when form is submitted by create button
            //unset postage textbox counter on success
            unset($this->getSession()->getSection('postage')->counter);
            
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
            
            $postage = $this->returnPostageArray($values);
            $listingID = $this->listings->createListing($id, $values, $imgLocSerialized);
            
            if (!empty($postage['options'])){
                $this->listings->writeListingPostageOptions($listingID, $postage);
            }
           
            $this->redirect("Listings:in");
        }
    }
    
    public function listingValidate($form){
        
        //verify form only in case it was posted
        //via "create button"
        
        if (!$form["add_postage"]->submittedBy){

            $this->formValidateValues($form);
            $images = $form->values['image'];
            $this->imgUpload($images, $form);
        }
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
    
    public function actionCreate(){
        
        //this code prevents showing multiple postage
        //textboxes on page reload
        if (is_null($this->request->getReferer())){
            unset($this->getSession()->getSection('postage')->counter);
            unset($this->getSession()->getSection("postage")->values);
            $this->redirect("Listings:in");
        }     
    }
    
    public function actionIn(){
        //when user goes to Listings:in for example by backward button
        //unset all the counters, to show only correct values
        unset($this->getSession()->getSection('postage')->counter);
        unset($this->getSession()->getSection("postage")->counterEdit);
        unset($this->getSession()->getSection("postage")->values);
    }

    public function handleDeleteListing($id){
        $this->listings->deleteListing($id);
    }
    
    public function handleDeletePostage($option, $id = NULL, $name = NULL){
        
        //functionality shared between createListing and editListing actions
        //if $name is null means that function has been called from createListing   
        if (!is_null($name)){
                
            $counter = &$this->getSession()->getSection('postage')->counterEdit;

            //decrease the counter for temporary field deletion only
            //if 0 unset totally to delete all fields from view
            if ($counter == 0){
                unset($counter);
            } else {
                $counter--;
            }

            $array = $this->listings->getPostageOptions($id);
            $ids = array();

            //assemble array and sort ids from lower to higher
            //to actually determine which postage record delete from db
            //MYSQL DELETE with LIMIT cannot be performed with OFFSET parameter
            foreach($array as $postage){
                array_push($ids, $postage['postage_id']);
            }

            asort($ids);

           //X in name means temporary field
           //if it's not temporary - delete from database
           if (strpos($name, "X") == FALSE){
                $this->listings->deletePostageOption($ids[$option]);
           }

           //redirect to the new form
           $this->redirect("Listings:editListing", $id);
        } else {
            
            //decreasing fields for create listing
            $counter = &$this->getSession()->getSection('postage')->counter;
            
            if ($counter == 0){
                unset($counter);
            } else {
                $counter--;
            }
        }
    }
    
    public function handleDisableListing($id){
        $this->listings->enableListing($id);
    }
    
    public function handleEnableListing($id){
        $this->listings->disableListing($id);
    }
    
    public function createComponentEditForm(){
        $frm = $this->formFactory->create();
        $cnt = count ($this->postageOptions);  
        $session = $this->getSession()->getSection("postage");

        
        for ($i = 0; $i<$cnt; $i++){

                $frm->addText("postage" . $i, "Doprava");
                $frm->addText("pprice" . $i, "Cena dopravy");

        }
            
        //additional postage textboxes logic
        $counter = $session->counterEdit;
        $values = $session->values;
        
        if (!is_null($counter)){
            
            $frm->addGroup("Postage");
        
            for ($i =0; $i<$counter; $i++){
    
                $frm->addText("postage" .$i. "X", "Doprava"); 
                $frm->addText("pprice" .$i. "X", "Cena");
            }
        }
          
        $frm->addSubmit("submit", "Upravit");
        $frm->addSubmit("add_postage", "Přidat dopravu")->onClick[] = function(){
            
            //inline onlclick handler, that counts postage options
            $session = $this->getSession()->getSection('postage');
            $counter = &$session->counterEdit;
            
            if ($counter <= self::MAX_POSTAGE_OPTIONS){
                $counter++;
            } else {
                $this->flashMessage("Dosáhli jste maxima poštovních možností.");
            }
            
            $form = $this->getComponent("editForm");
            $session->values = $form->getValues(TRUE);
            
            $listingID = $this->getSession()->getSection('listing')->listingID;
            $this->redirect("Listings:editListing", $listingID);
        };
           
        $this->fillForm($frm, $values);
                    
        $frm->onSuccess[] = array($this, 'editSuccess');
        $frm->onValidate[] = array($this, 'editValidate');
        $frm->onSubmit[] = array($this, 'editSubmit');
        
        return $frm;    
    }
    
    private $actualListingValues;
    private $listingImages;
    private $listingID;
    private $postageOptions;
    
    public function actionEditListing($id){
                            
        //TODO use ACL
        if ($this->listings->getAuthor($id)['author'] !== $this->returnLogin()){
            $this->redirect("Listings:in");
        } else {
           $this->actualListingValues = $this->listings->getActualListingValues($id);
           $this->postageOptions = $this->listings->getPostageOptions($id);
        }
        
        $listingImages = $this->listings->getListingImages($id);
        $imgSession = $this->getSession()->getSection('images');
        $imgSession->listingImages = $listingImages;
        $listingSession = $this->getSession()->getSection('listing');
        $listingSession->listingID = $id;
    }

    public function handleDeleteClick($img){

        $images = $this->getSession()->getSection('images');
        $images->toDelete = $img;
        $this->redirect("Listings:alert");       
    }
    
    public function handleDeleteImage(){

       $session = $this->getSession()->getSection('images');
       $img =  $session->toDelete;
       $listingID = $this->getSession()->getSection('listing')->listingID;

       $imgs = $this->listings->getListingImages($listingID);
       
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
    
    //stores actual listing values into session
    private function setListingSession($id){
        $listingDetails = $this->listings->getActualListingValues($id);
    	$session = $this->getSession()->getSection('listing');
    	$session->listingDetails = $listingDetails;
    }
    
    public function actionView($id){
        
        //URL not wanted
        $n = "/listings/view";
        
        //catch corner cases
        if (is_null($id)){
            $this->redirect("Dashboard:in");
        }
         
        else if ($this->URL == $n){
            $this->redirect("Dashboard:in");
        }
        
        else if ($this->URL == $n . "/"){
            $this->redirect("Dashboard:in");
        }
    	
        else {
            $this->setListingSession($id);
            $session = $this->getSession()->getSection('images');
            $session->listingImages = $this->listings->getListingImages($id);
        }
    }

    public function actionBuy($id){
        
        //URL not wanted
        $n = "/listings/buy";
        
        //catch corner cases
        if (is_null($this->request->getReferer()) && !is_null($id)){
            $this->redirect("Listings:view", $id);
        }
        
        else if (is_null($this->request->getReferer()) && is_null($id)){
            $this->redirect("Dashboard:in");
        }
        
        else if ($this->URL == $n){
            $this->redirect("Dashboard:in");
        }
       
        else if ($this->URL == $n . "/"){
            $this->redirect("Dashboard:in");
        }
        
        else {
            $this->setListingSession($id);
        } 
    }

    public function createComponentVendorNotesForm(){
        
       $form = $this->vendorNotes->create();
       $form->onSuccess[] = array($this, 'vendorNotesSuccess');
       $form->onValidate[] = array($this, 'vendorNotesValidate');
       
       return $form;
    }
    
    public function vendorNotesSuccess($form){
        
        $session = $this->getSession()->getSection('listing');

        //assemble argumets array
        $listingID = $session->listingDetails->id;
        $productName = $session->listingDetails['product_name'];
        $userid = $this->returnId();
        $quantity = $session->postageDetails['quantity'];
        $postage = $session->postageDetails['postage'];
        $buyerNotes = $form->getValues(TRUE)['notes'];
        $date = date("j. n. Y");  
        
        $arguments  = array ("id" => $userid, "listing_id" => $listingID, 
            "product_name" => $productName, "date_ordered" => $date,
            "quantity" => $quantity, "postage" => $postage, "buyer_notes" => $buyerNotes);

        //and write new order to database
        $this->orders->writeOrderToDb($arguments);
        
        //redirect user to his order list 
        //after item succesfully bought
        $this->flashMessage('Operace proběhla úspěšně.'); 
        $this->redirect('Orders:in');
    }
    
    public function vendorNotesValidate($form){
        
        if ($form['zrusit']->submittedBy) {

          $listingID = $this->getSession()->getSection('listing')->listingDetails->id;   
          $this->redirect('Listings:view', $listingID);
        }

        //is submitted data PGP? method placeholder
        //TODO
    }
    
    public function handleSetMainImage($imgNum){
        
        $listingID = $this->getSession()->getSection('listing')->listingID;     
        $this->listings->setListingMainImage($listingID, $imgNum);
    }
    
    public function createComponentBuyForm(){
        
        $form = new Nette\Application\UI\Form;
        
        $listingID =  $this->getSession()->getSection('listing')->listingDetails->id;
        
        $postageOptionsDB = $this->listings->getPostageOptions($listingID);
        $postageOptions = array();
        
        foreach($postageOptionsDB as $option){
            array_push($postageOptions, $option['option'] . "+" . $option['price'] . "Kč");
        }
        
        $form->addSelect("postage", "Možnosti zásilky:")->setItems($postageOptions, FALSE);
         //    ->addRule($form::FILLED, "Vyberte prosím některou z možností zásilky.");
        
        $form->addText('quantity', 'Množství:')
             ->addRule($form::FILLED, "Vyplňte prosím množství.")
             ->addRule($form::INTEGER, 'Množství musí být číslo')
             ->addRule($form::RANGE, 'Množství 1 až 99 maximum.', array(1, 99));
        
        $form->addSubmit("koupit", "Koupit");
        
        $form->onSuccess[] = array($this, 'buyFormOnSuccess');
        
        return $form;
    }
    
    public function buyFormOnSuccess($form){
        $session = $this->getSession()->getSection('listing');
        $listingID = $session->listingDetails->id;
        $session->postageDetails = $form->getValues(TRUE);
        
        $this->redirect("Listings:Buy", $listingID);
    }
    
    public $id;
    
    public function editSubmit($form){
        $values = $form->getValues(TRUE);
        
        $this->getSession()->getSection('postage')->values = $values;
        $listingID = $this->getSession()->getSection('listing')->listingID;
        $this->redirect("Listings:editListing", $listingID); 
    }
    
    public function editSuccess($form){
        
        if (!$form['add_postage']->submittedBy){
            
            unset($this->getSession()->getSection('postage')->counterEdit);

            $id = $this->actualListingValues['id'];
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

            $existingImages = $this->listings->getListingImages($listingID);
            $new_images = serialize(array_merge($imageLocations, $existingImages));

            $postageToUpdate = $this->returnPostageArray($values);
            $postageFromDB = $this->listings->getPostageOptions($listingID);

            //asseble array with new postage values and database ids to edit
            $arrayToWrite = array();

            $cnt = 0;

            foreach($postageFromDB as $option){

                if ($postageToUpdate['options'][$cnt] !== $option['option']){
                    $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                    $arrayToWrite[$cnt]['option'] = $postageToUpdate['options'][$cnt];
                }

                if ($postageToUpdate['prices'][$cnt] !== (string) $option['price']){
                    $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                    $arrayToWrite[$cnt]['price'] = $postageToUpdate['prices'][$cnt];
                }

                $cnt++; 
            }
            
            $postageAdditional = $this->returnPostageArray($values, TRUE);
            
            if (!empty($postageAdditional['options'])){
                $this->listings->writeListingPostageOptions($listingID, $postageAdditional);
            }
            
            $this->listings->updateListingImages($listingID, $new_images);   
            $this->listings->updatePostageOptions($arrayToWrite);
            $this->listings->editListing($id, $values);
            $this->flashMessage("Listing uspesne upraven!");
            $this->redirect("Listings:editListing", $listingID); 
        }
    }
    
    public function editValidate($form){

        if (!$form['add_postage']->submittedBy){
            $this->formValidateValues($form);
            $images = $form->values['image'];
            $this->imgUpload($images, $form);
        }
    }
    
    public function beforeRender(){
        
        $login = $this->getUser()->getIdentity()->login;

        //query bitcoind, get response
        $btcClient = $this->btcClient;
        $command = new Command('getbalance', $login);             
        $response = $btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true);

        $section =  $this->getSession()->getSection('balance');
        $section->balance = $result['result'];

        //render edit form with actual values from database
        $component_iterator = $this->getComponent("editForm")->getComponents();
        $componenty = iterator_to_array($component_iterator);

        if ($this->actualListingValues != null){
            
            $optArray = $this->postageOptions;
            $postageCounter = $priceCounter = 0;
               
            foreach ($componenty as $comp){
                switch($comp->name){
                    
                    case 'product_name':
                        $comp->setValue($this->actualListingValues['product_name']);
                        break;
                    case 'product_desc':
                        $comp->setValue($this->actualListingValues['product_desc']);
                        break;
                    case 'price':
                        $comp->setValue($this->actualListingValues['price']);
                        break;
                    case 'ships_from':
                        $comp->setValue($this->actualListingValues['ships_from']);
                        break;
                    case 'ships_to';
                        $comp->setValue($this->actualListingValues['ships_to']);
                        break;
                    case 'product_type':
                        $comp->setValue($this->actualListingValues['ships_to']);
                        break;
                    case (strpos($comp->name, "postage")):
                        //render all postage options into correct textboxes
                        if (array_key_exists($postageCounter, $optArray)){
                            $comp->setValue($optArray[$postageCounter]['option']);
                            $postageCounter++;
                        }
                        break; 
                    case (strpos($comp->name, "pprice")):
                        //render postage prices into correct textboxes
                        if (array_key_exists($priceCounter, $optArray)){
                            $comp->setValue($optArray[$priceCounter]['price']);
                            $priceCounter++;  
                        }    
                        break; 
                }
            }

        } else {

        }
        
        //template variables shared between templates    
        $urlPath = $this->URL->path;
     
        if ( strpos($urlPath, "edit" )|| strpos($urlPath, "view") || strpos($urlPath, "buy")){
            $this->template->listingImages = $this->getSession()->getSection('images')->listingImages;
            $this->template->listingID = $this->getSession()->getSection('listing')->listingID;
            $this->template->listingDetails = $this->getSession()->getSection('listing')->listingDetails;
        }
    }
    
    public function renderIn(){
        
        //render variables for single template - Listings:in    
        $login = $this->getUser()->getIdentity()->login;
        $id = $this->getUser()->getIdentity()->getId();
        $this->template->isVendor = $this->listings->isVendor($id);
        $this->template->listings = $this->listings->getListings($login);    
        $this->template->currentUser = $this->returnLogin();  
    }
}