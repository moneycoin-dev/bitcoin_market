<?php 

namespace App\Presenters;

use Nette;
use App\Model\Listings;
use App\Model\Orders;
use App\Model\UserManager;
use App\Forms\ListingFormFactory;
use App\Forms\VendorNotesFactory;

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
    
    private function isListingAuthor($id){
        //checks whether user is author of the listing
        $listingAuthor = $this->listings->getAuthor($id);
        $actualUser = $this->returnLogin();
        
        if ($listingAuthor == $actualUser){
            return TRUE;
        } else {
            return FALSE;
        }
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
    
    private function constructCheckboxList($form){
        $listingOptions = array("ms" => "Multisig");
        
        if($this->userManager->hasFEallowed($this->returnLogin())){
            $listingOptions["fe"] = "Finalize Early";
        }
        
        return $form->addCheckboxList("listingOptions", "Options", $listingOptions);
    }
    
    private function listSess(){
        return $this->getSession()->getSection('listing');
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
    
    protected $userManager;

    public function injectUserManager(UserManager $um){
        $this->userManager = $um;
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
        
        $this->constructCheckboxList($form);  
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
    
    private function getProcValues($values, $type = NULL){
        
        //add listing author to values
        $values['author'] = $this->returnLogin();

        //add type of listing to values according to checkboxes set
        if (in_array("ms", $values["listingOptions"])){
            $values["MS"] = "yes";
        } else {
            $values["MS"] = "no";
        }

        if (in_array("fe", $values["listingOptions"])){
            $values["FE"] = "yes";
        } else {
            $values["FE"] = "no";
        }

        //pass to DB - no existing column
        unset($values["listingOptions"]);

        $imageLocations = array();
        $images = $values['image'];

        //pass to DB - no existing column
        unset($values['image']);

        foreach ($images as $image){
            //get relative path to image for webserver &
            //save relative paths into array
            array_push($imageLocations, substr($image->getTemporaryFile(),
                    strpos($image->getTemporaryFile(), "userfiles/")));     
        }
        
        if (!isset($type)){
            //serialize img locations to store it in one field in db
            $imgLocSerialized = serialize($imageLocations);
            $values['product_images'] = $imgLocSerialized;
        } else {
            //edit listing branch
            $values['imageLocations'] = $imageLocations;
        }
        
        return $values;
    }
    
    public function listingCreate($form){
       
        ///do things only if submited by "create button"
        if (!$form['add_postage']->submittedBy){
            
            //things to do when form is submitted by create button
            //unset postage textbox counter on success
            unset($this->getSession()->getSection('postage')->counter);
            
            $values = $this->getProcValues($form->getValues(True));
            $postage = $this->returnPostageArray($values);
            $listingID = $this->listings->createListing($values);
            
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
        
        $login =  $this->returnLogin();
  
        if ($this->wallet->getBalance($login) > 1){
         
            $this->listings->becomeVendor($login);
            $this->flashMessage("Váš účet má nyní vendor status");
            $this->redirect("Listings:in");
        } 
        
        else if ($this->listings->isVendor($login)) {
            //if user isalready vendor
            //redirect him in case he accidentaly visists
            //this page
            $this->redirect("Listings:in");
        }
        
        else {
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
        
        if ($this->isListingAuthor($id)){
            $this->listings->disableListing($id);
            $this->redirect("Listings:in");
        } else {
            $this->redirect("Listings:in");
        }
    }
    
    public function handleEnableListing($id){
        
        if ($this->isListingAuthor($id)){
            $this->listings->enableListing($id);
            $this->redirect("Listings:in");
        } else {
            $this->redirect("Listings:in");
        }
    }
    
    public function createComponentEditForm(){
        $frm = $this->formFactory->create();
        $listingID = $this->getSession()->getSection('listing')->listingID;
        
        //query database for listing type
        $FE = $this->listings->isListingFE($listingID);
        $MS = $this->listings->isListingMultisig($listingID);
        
        //checkbox value rendering logic
        $checkVal = array();
        
        if ($MS){
            $checkVal["ms"] = "ms";
        }
        
        if ($FE){
            $checkVal["fe"] = "fe";
        }
        
        $this->constructCheckboxList($frm)->setValue($checkVal);
        
        //discard option array
        unset($checkVal);

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
        $frm->addSubmit("add_postage", "Přidat dopravu")->onClick[] = function() use($listingID) {
            
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
            
            $this->redirect("Listings:editListing", $listingID);
        };
           
        $this->fillForm($frm, $values);
                    
        $frm->onSuccess[] = array($this, 'editSuccess');
        $frm->onValidate[] = array($this, 'editValidate');
        
        return $frm;    
    }
    
    private $actualListingValues;
    private $listingImages;
    private $listingID;
    private $postageOptions;
    
    public function actionEditListing($id){
                            
        if ($this->isListingAuthor($id)){
           $this->actualListingValues = $this->listings->getActualListingValues($id);
           $this->postageOptions = $this->listings->getPostageOptions($id);
        } else {       
           $this->redirect("Listings:in");
        }
        
        $listingImages = $this->listings->getListingImages($id);
        $imgSession = $this->getSession()->getSection('images');
        $imgSession->listingImages = $listingImages;
        $listingSession = $this->listSess();
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
    	$session = $this->listSess();
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
            //in case that listing is not currently active
            //redirect potentional viewer to his dashboard
            if ($this->listings->isListingActive($id)){
                $this->setListingSession($id);
                $session = $this->getSession()->getSection('images');
                $session->listingImages = $this->listings->getListingImages($id);
            } else {
                $this->redirect("Dashboard:in");
            }
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
            if ($this->listings->isListingActive($id)){
                
                //prevent buying from myself scenario
                
                $login = $this->returnLogin();
                $listingAuthor = $this->listings->getAuthor($id);
                
                if ($login != $listingAuthor){
                    $this->setListingSession($id);
                } else {
                    $this->redirect("Listings:view", $id);
                }
                
            } else {
                $this->redirect("Dashboard:in");
            }
        } 
    }

    public function createComponentVendorNotesForm(){
        
       $form = $this->vendorNotes->create();
       $form->onSuccess[] = array($this, 'vendorNotesSuccess');
       $form->onValidate[] = array($this, 'vendorNotesValidate');
       
       return $form;
    }
    
    public function vendorNotesSuccess($form){
        
        $session = $this->listSess();

        //assemble argumets array
        $listingID = $session->listingDetails->id;
        $productName = $session->listingDetails['product_name'];
        $author = $this->listings->getAuthor($listingID);
        $quantity = $session->postageDetails['quantity'];
        $postage = $session->postageDetails['postage'];
        $buyerNotes = $form->getValues(TRUE)['notes'];
        $date = date("j. n. Y"); 
        $buyer = $this->returnLogin();   
          
        $arguments  = array ("author" => $author, "listing_id" => $listingID, 
            "product_name" => $productName, "date_ordered" => $date,
            "quantity" => $quantity, "postage" => $postage, "buyer_notes" => $buyerNotes,
            "buyer" => $buyer, "status" => "pending", "final_price" => $session->finalPrice);

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
        
        $listingID = $this->getSession()->getSection("listing")->listingID;     
        $this->listings->setListingMainImage($listingID, $imgNum);
    }
    
    public function createComponentBuyForm(){
        
        $form = new Nette\Application\UI\Form;
        
        $session = $this->getSession()->getSection("listing");
        $listingID = $session->listingDetails->id;
        
        $postageOptionsDB = $this->listings->getPostageOptions($listingID);
        $postageOptions = array();
        $postageIDs = array();
        
        foreach($postageOptionsDB as $option){
            array_push($postageOptions, $option["option"] . " +" . $option["price"] . "Kč");
            array_push($postageIDs, $option["postage_id"]);
        }
        
        asort($postageIDs);
        
        //store for later check, that selectbox was not maliciously altered
        $session->postageIDs = $postageIDs;
         
        $form->addSelect("postage", "Možnosti zásilky:")->setItems($postageOptions, FALSE);
         //    ->addRule($form::FILLED, "Vyberte prosím některou z možností zásilky.");
        
        $form->addText('quantity', 'Množství:')
             ->addRule($form::FILLED, "Vyplňte prosím množství.")
             ->addRule($form::INTEGER, "Množství musí být číslo")
             ->addRule($form::RANGE, "Množství 1 až 99 maximum.", array(1, 99));
       
        $form->addSubmit("koupit", "Koupit");
        
        $form->onSuccess[] = array($this, "buyFormOnSuccess");
        $form->onValidate[] = array($this, "buyFormValidate");
        
        return $form;
    }
    
    public function buyFormOnSuccess($form){
        $session = $this->getSession()->getSection("listing");
        $listingID = $session->listingDetails->id;
        $session->postageDetails = $form->getValues(TRUE);
        
        $this->redirect("Listings:Buy", $listingID);
    }
    
    public function buyFormValidate($form){
        
        //parse selectbox value
        $postageString = str_replace(" ","",$form->values->postage);
        $extractedOption = substr($postageString, 0, strpos($postageString, "+"));
        $extractedPostagePrice = intval(substr($postageString, strpos($postageString, "+")+1, -3));
        
        $session = $this->listSess();
        
        $ids = $session->postageIDs;
        unset($this->getSession()->getSection("listing")->postageIDs);
        
        //check that selectbox has not been altered
        if (!$this->listings->verifyPostage($ids, $extractedOption, $extractedPostagePrice)){
            $form->addError("Detekována modifikace hodnoty selectboxu!");
        }
        
        $listingDetails = $session->listingDetails;
        
        //price conversions and user balance check
        $converter = new \PriceConverter();
        $postageBTC = $converter->convertCzkToBTC($extractedPostagePrice);
        $listingBTC = $converter->convertCzkToBTC($listingDetails->price);
        $finalPrice = $listingBTC + $postageBTC;
        $session->finalPrice = $finalPrice;
        $userBalance = $this->wallet->getBalance($this->returnLogin());
        
        if (!($userBalance >= $finalPrice)){
            $form->addError("Nemáte dostatečný počet bitcoinů pro zakoupení produktu.");
        }
    }
    
    public $id;
    
    public function editSuccess($form){
        
        if (!$form['add_postage']->submittedBy){
            
            unset($this->getSession()->getSection('postage')->counterEdit);

            $values = $this->getProcValues($form->getValues(TRUE), "edit");
            $listingID = $this->getSession()->getSection('listing')->listingID;
            $existingImages = $this->listings->getListingImages($listingID);
            $new_images = serialize(array_merge($values["imageLocations"], $existingImages));
            
            unset($values["imageLocations"]);

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
            
            $id = $this->actualListingValues['id'];
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
        $login = $this->returnLogin();
        $this->template->isVendor = $this->listings->isVendor($login);
        $this->template->listings = $this->listings->getListings($login);    
        $this->template->currentUser = $this->returnLogin();  
    }
}