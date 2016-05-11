<?php 

namespace App\Presenters;

use Nette;
use App\Model\Listings;
use App\Helpers\ListingsHelper;
use App\Forms\ListingFormFactory;
use App\Forms\VendorNotesFactory;

/**
 * 
 * @what Vendor Listings Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class ListingsPresenter extends ProtectedPresenter {
    
    protected $listings, $formFactory,
              $vendorNotes, $URL, $request, $lHelp;
    
    const MAX_POSTAGE_OPTIONS = 5;
  
    public function __construct(Nette\Http\Request $r){
        parent::__construct();
        
        $this->request = $r; 
        $this->URL = $r->getUrl();
    }
    
    public function injectHelper(ListingsHelper $lh){
        $this->lHelp = $lh;
    }
    
    public function injectListings(Listings $list){
        $this->listings = $list;
    }
    
    public function injectListingForm(ListingFormFactory $factory){
        $this->formFactory = $factory;
    }
    
    public function injectVendorNotes(VendorNotesFactory $vendorNotes){
        $this->vendorNotes = $vendorNotes;
    }
        
    public function createComponentListingForm(){
        
        $form = $this->formFactory->create();
        
        $this->lHelp->constructCheckboxList($form);  
        $form->addSubmit("submit", "Vytvořit");
        $form->addSubmit("add_postage", "Přidat dopravu")->onClick[] = 
        function (){
            
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
        
        $session = $this->hlp->sess("postage");
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
        $this->lHelp->fillForm($form, $values);
     
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');    
        $form->onSuccess[] = array($this, 'listingCreate');
        $form->onValidate[] = array($this, 'listingValidate');
               
        return $form;
    }
    
    public function listingCreate($form){
       
        ///do things only if submited by "create button"
        if (!$form['add_postage']->submittedBy){
            
            //things to do when form is submitted by create button
            //unset postage textbox counter on success
            unset($this->hlp->sess("postage")->counter);
            
            $values = $form->getValues(True);
            $postage = $this->lHelp->returnPostageArray($values);
            $procValues = $this->lHelp->getProcValues($values);         
            $listingID = $this->listings->create($procValues);
            
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
            $this->lHelp->formValidateValues($form);
            $images = $form->values['image'];
            $this->lHelp->imgUpload($images, $form);
        }
    }
    
    public function handleVendor(){
        
        $login =  $this->hlp->logn();
  
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
            $s = $this->hlp->sess("postage");
            unset($s->counter, $s->values);
            $this->redirect("Listings:in");
        }     
    }
    
    public function actionIn(){
        //when user goes to Listings:in for example by backward button
        //unset all the counters, to show only correct values
        $s = $this->hlp->sess("postage");
        unset($s->counter, $s->counterEdit, $s->values);
    }

    public function handleDeleteListing($id){
        $this->listings->delete($id);
    }
    
    public function handleDeletePostage($option, $id = NULL, $name = NULL){
        
        //functionality shared between createListing and editListing actions
        //if $name is null means that function has been called from createListing   
        if (!is_null($name)){
                
            $counter = &$this->hlp->sess("postage")->counterEdit;

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
            $counter = &$this->hlp->sess("postage")->counter;
            
            if ($counter == 0){
                unset($counter);
            } else {
                $counter--;
            }
        }
    }
    
    public function handleDisableListing($id){
        
        $login = $this->hlp->logn();
        
        if ($this->listings->isListingAuthor($id, $login)){
            $this->listings->disable($id);
            $this->redirect("Listings:in");
        } else {
            $this->redirect("Listings:in");
        }
    }
    
    public function handleEnableListing($id){     
        $login = $this->hlp->logn();
        
        if ($this->listings->isListingAuthor($id, $login)){       
            if (!empty($this->listings->getPostageOptions($id))){
                $this->listings->enable($id);
            } else {
                $this->flashMessage("Prosím přidejte poštovní možnosti před zveřejněním Vašeho listingu.");
            }
        } 
        $this->redirect("Listings:in");
    }
    
    public function createComponentEditForm(){
        $frm = $this->formFactory->create();
        $listingID = $this->hlp->sess("listing")->listingID;
        
        //query database for listing type
        $FE = $this->listings->isFE($listingID);
        $MS = $this->listings->isMultisig($listingID);
        
        //checkbox value rendering logic
        $checkVal = array();
        
        if ($MS){
            $checkVal["ms"] = "ms";
        }
        
        if ($FE){
            $checkVal["fe"] = "fe";
        }
        
        $this->lHelp->constructCheckboxList($frm)->setValue($checkVal);
        
        //discard option array
        unset($checkVal);

        $cnt = count ($this->postageOptions);  
        $session = $this->hlp->sess("postage");

        
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
        $frm->addSubmit("add_postage", "Přidat dopravu")->onClick[] = 
                
        function() use($listingID) {
            
            //inline onlclick handler, that counts postage options
            $session = $this->hlp->sess("postage");
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
           
        $this->lHelp->fillForm($frm, $values);                
        $frm->onSuccess[] = array($this, 'editSuccess');
        $frm->onValidate[] = array($this, 'editValidate');
        
        return $frm;    
    }
    
    protected $actualListingValues, $listingImages,
            $listingID, $postageOptions;
    
    public function actionEditListing($id){
        
        $login = $this->hlp->logn();
                            
        if ($this->listings->isListingAuthor($id, $login)){
           $this->actualListingValues = $this->listings->getActualListingValues($id);
           $this->postageOptions = $this->listings->getPostageOptions($id);
        } else {       
           $this->redirect("Listings:in");
        }
        
        $listingImages = $this->listings->getListingImages($id);
        $imgSession = $this->hlp->sess("images");
        $imgSession->listingImages = $listingImages;
        $listingSession = $this->hlp->sess("listing");
        $listingSession->listingID = $id;
    }

    public function handleDeleteClick($img){

        $images = $this->hlp->sess("images");
        $images->toDelete = $img;
        $this->redirect("Listings:alert");       
    }
    
    /**
     * Handles image deletion from database
     */
    public function handleDeleteImage(){

       $session = $this->hlp->sess("images");
       $img =  $session->toDelete;
       $listingID = $this->hlp->sess("listing")->listingID;
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

    /**
     * Handles abort link from alert template
     */
    public function handleDeleteAbort(){
        $listingID = $this->hlp->sess("listing")->listingID;
        $this->redirect("Listings:editListing", $listingID);
    }
   
    /**
     * Redirects if and when user manually tinkered with URL
     * otherwise sets listing details session for later use
     * 
     * @param int $id Listing id from URL
     */
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
            if ($this->listings->isActive($id)){
                $this->lHelp->sessNcheck($id);
                $session = $this->hlp->sess("images");
                $session->listingImages = $this->listings
                                               ->getListingImages($id);
            } else {
                $this->redirect("Dashboard:in");
            }
        }
    }
    
    /**
     * Redirects if and when user manually tinkered with URL
     * for particular URL
     * 
     * @param int $id Listing id from URL
     */
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
            if (!$this->listings->isActive($id)){
                $this->redirect("Dashboard:in");
            }
        } 
    }

    /**
     * Creates vendor notes form
     * @return Form
     */
    public function createComponentVendorNotesForm(){  
       $form = $this->vendorNotes->create();
       $form->onSuccess[] = array($this, 'vendorNotesSuccess');
       $form->onValidate[] = array($this, 'vendorNotesValidate');
       
       return $form;
    }
    
    /**
     * Vendor Notes success callback
     * Creates order in database, moves funds around
     * @param Form $form
     */
    public function vendorNotesSuccess($form){   
        $session = $this->hlp->sess("listing");
        $date_ordered = date("j. n. Y"); 
        $buyer = $this->hlp->logn();
        $status = "pending";
        $buyer_notes = $form->getValues(TRUE)['notes'];
        $final_price = $session->finalPriceBTC; 
        $quantity = $session->postageDetails['quantity'];
        $postage = $session->postageDetails['postage'];    
        $product_name = $session->listingDetails['product_name'];
        $listing_id = $session->listingDetails->id;
        $author = $this->listings->getAuthor($listing_id);
        $FE = TRUE; //$this->listings->isListingFE($listingID) ? "yes" : "no";
        
        //save order to DB and do BTC transactions
        //only if balance is sufficient
        // if ($this->wallet->balanceCheck($buyer, $price)){
        
            //unset unneccessary vars and assemble arg array
            //from these that remains
            unset($form, $session);
            $arguments = $this->orders->asArg(get_defined_vars());
            
            //and write new order to database
            $order_id = $this->orders->saveToDB($arguments);

            //value that seller will receive and market profit
            $commisioned = $this->converter->getCommisioned($final_price);
            $marketProfit = $this->converter->getMarketProfit($final_price);
            $wallet = $this->wallet;
            
            //save and transact market profit
            $wallet->moveAndStore("saveprofit", $buyer, "profit", $marketProfit, $order_id);
        
            //move funds and store trasactions into db
            if($FE != "yes"){  
                //escrow branch
                $wallet->moveAndStore("pay", $buyer, "escrow", $commisioned, $order_id, "yes");
                $this->flashMessage("Operace proběhla úspěšně. Platba je bezpečně uložena v Escrow."); 
                $this->redirect('Orders:in');
            } else { 
                
                //FE - immediately transfer funds and redirect user to feedback
                $this->orders->finalize($order_id);
                $wallet->moveAndStore("pay", $buyer, $author, $commisioned, $order_id, "no");
                $this->flashMessage("Finalize Early - Platba převedena na vendorův účet.");
                $this->flashMessage("Zanechte feedback - Můžete později změnit ve Vašich objednávkách.");
                $this->redirect("Orders:Feedback", $order_id);
            }
        /*    
        } else {
            $this->flashMessage("Něco se pokazilo. Prosím kontaktujte administrátora.");
            $this->redirect("Orders:in");
        } */
    }
    
    /**
     * Vendor notes form validation callback
     * @param Form $form
     */
    public function vendorNotesValidate($form){
        
        if ($form['zrusit']->submittedBy) {

          $listingID = $this->hlp->sess("listing")->listingDetails->id;   
          $this->redirect('Listings:view', $listingID);
        }

        //is submitted data PGP? method placeholder
        //TODO
    }
    
    /**
     * Sets Listing main image to display
     * @param int $imgNum img number from template
     */
    public function handleSetMainImage($imgNum){ 
        $listingID = $this->hlp->sess("listing")->listingID;     
        $this->listings->setMainImage($listingID, $imgNum);
    }
    
    /**
     * Creates Buy Form with rendered postage options 
     * from database
     * 
     * @param Form $form
     */
    public function createComponentBuyForm(){ 
        $form = new Nette\Application\UI\Form;
        
        $session = $this->hlp->sess("listing");
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
    
    /**
     * Buy form success callback
     * @param Form $form
     */
    public function buyFormOnSuccess($form){
        $session = $this->hlp->sess("listing");
        $listingID = $session->listingDetails->id;
        $session->postageDetails = $form->getValues(TRUE);
        
        $this->redirect("Listings:Buy", $listingID);
    }
    
    /**
     * Buy form validate callback
     * @param Form $form
     */
    public function buyFormValidate($form){
       
        //parse selectbox value
        $postageString = str_replace(" ","",$form->values->postage);
        $extractedOption = substr($postageString, 0, strpos($postageString, "+"));
        $pPriceCZK = intval(substr($postageString, strpos($postageString, "+")+1, -3));

        $session = $this->hlp->sess("listing");    
        $ids = $session->postageIDs;
        unset($session->postageIDs);

        //check that selectbox has not been altered
        if (!$this->listings->verifyPostage($ids, $extractedOption, $pPriceCZK)){
            $form->addError("Detekována modifikace hodnoty selectboxu!");
        }

        //get listing info
        $listingDetails = $session->listingDetails;
        $lPriceCZK = $listingDetails->price;

        //czk price conversions to BTC
        $converter  = $this->converter;
        $postageBTC = $converter->convertCzkToBTC($pPriceCZK);
        $listingBTC = $converter->convertCzkToBTC($lPriceCZK);

        //final price calculation
        $quantity = $form->values->quantity;
        $finalPriceBTC = $listingBTC + $postageBTC;
        $finalPriceCZK = $pPriceCZK + $lPriceCZK;
        $session->finalPriceBTC = $finalPriceBTC * $quantity;
        $session->finalPriceCZK = $finalPriceCZK * $quantity;

       // $this->lHelp->balanceCheck($this->hlp->logn(), $finalPrice, $form);   
    }
    
    /**
     * Edit form success callback
     * @param Form $form
     */
    public function editSuccess($form){
        
        if (!$form['add_postage']->submittedBy){
            
            unset($this->hlp->sess("postage")->counterEdit);

            $values = $form->getValues(TRUE);
            $procValues = $this->lHelp->getProcValues($values, "edit");
            $listingID = $this->actualListingValues["id"];
            $pToUpdate = $this->lHelp->returnPostageArray($values);
            $pFromDB = $this->listings->getPostageOptions($listingID);
            $arrayToWrite = $this->lHelp->arrayToWrite($pToUpdate, $pFromDB);
            $postageAdd = $this->lHelp->returnPostageArray($values, TRUE);
            
            //compare old with new values 
            //and perform neccessary db operations
            if (!empty($postageAdd['options'])){
                $this->listings->writeListingPostageOptions($listingID, $postageAdd);
            }
            
            if (!empty($procValues["n_img"])){
                $this->listings->updateListingImages($listingID, $procValues["n_img"]);  
                unset($procValues["n_img"]);
            }
            
            if ($pFromDB !== $arrayToWrite){
                $this->listings->updatePostageOptions($arrayToWrite);
            }
            
            //remove undue element for comparsion
            unset($this->actualListingValues["id"]);
            
            if ($this->actualListingValues != $procValues){
                $this->listings->edit($listingID, $procValues);
            }
            
            $this->flashMessage("Listing uspesne upraven!");
         //   $this->redirect("Listings:editListing", $listingID); 
        }
    }
    
    /**
     * Edit form validation callback
     * @param Form $form
     */
    public function editValidate($form){
        if (!$form['add_postage']->submittedBy){
            $this->lHelp->formValidateValues($form);
            $images = $form->values['image'];
            $this->lHelp->imgUpload($images, $form);
        }
    }
    
    /**
     * Checks for listing values into db
     * And renders edit listing form according to it
     */
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
        }
         
        $urlPath = $this->URL->path;
     
        //template variables shared between templates
        if ( strpos($urlPath, "edit" )|| strpos($urlPath, "view") 
                || strpos($urlPath, "buy")){
            
            $lst = $this->hlp->sess("listing");
            $imgs = $this->hlp->sess("images");
            $this->template->listingID = $lst->listingID;
            $this->template->listingDetails = $lst->listingDetails;
            $this->template->listingImages = $imgs->listingImages;
        }
    }
    
    /**
     * Renders Listing buy page template variables
     */
    public function renderBuy(){
        $lst = $this->hlp->sess("listing");
        $this->template->finalPriceBTC = $lst->finalPriceBTC;
        $this->template->finalPriceCZK = $lst->finalPriceCZK;
    }
    
    /**
     * Renders Listing view page template variables
     * @param int $id listing id from URL
     */
    public function renderView($id){    
        if ($this->hlp->sess("listing")->render){
            $this->template->renderBuyForm = TRUE;
        }
        if ($this->listings->hasFeedback($id)){
            $this->template->feedback = $this->listings->getFeedback($id);
        }
    }
    
    /**
     *  Renders Listings entry page template variables
     */
    public function renderIn(){  
        $login = $this->hlp->logn();
        $this->template->isVendor = $this->listings->isVendor($login);
        $this->template->listings = $this->listings->getListings($login);    
        $this->template->currentUser = $login;
    }
}