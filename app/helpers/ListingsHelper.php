<?php

namespace App\Helpers;

use App\Helpers\BaseHelper;
use App\Model\Settings;
use App\Model\Listings;

/**
 * 
 * @what Helper class for Listings Presenter
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class ListingsHelper extends \Nette\Object {
    
    /** @var App\Model\Listings */
    protected $listings;
    
    /** @var App\Model\Settings */
    protected $settings;
    
    /** @var App\Model\BaseHelper */
    protected $base;
    
    /**
     * Dependency injection
     * @param BaseHelper $bh
     */
    public function injectBase(BaseHelper $bh){
        $this->base = $bh;
    }

    /**
     * Dependency injection
     * @param Listings $ls
     */
    public function injectListings(Listings $ls){
        $this->listings = $ls;
    }

    /**
     * Dependency injection
     * @param Settings $s
     */
    public function injectSettings(Settings $s){
        $this->settings = $s;
    }

    /**
     * Create & Edit Listing
     * randomized image uploads.
     * 
     * @param array $images
     * @param Form $form
     */
    public function imgUpload($images, $form){
        foreach($images as $image){     
            if ($image->isOk() && $image->isImage()){
                $filesPath = getcwd() . "/userfiles/" ;
                $username = $this->base->logn();
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
    
    /**
     * Create array with postage options 
     * and postage prices, then save it to db
     * with special function.
     * 
     * @param array $values
     * @param bool $flag
     * @return array
     */
    public function returnPostageArray($values, $flag = NULL){
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
    
    /**
     * Fills form after submission.
     * with data from session.
     * Used when adding postage options.
     * 
     * @param Form $form
     * @param array $values
     */
    public function fillForm($form , $values){
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

    /**
     * Consruct checkboxes for
     * listing creation, dependent on 
     * vendor privileges.
     * 
     * @param Form $form
     * @return Nette\Forms\Controls\CheckboxList
     */
    public function constructCheckboxList($form){
        $listingOptions = array("ms" => "Multisig");

        if($this->settings->hasFEallowed($this->base->logn())){
            $listingOptions["fe"] = "Finalize Early";
        }
        
        return $form->addCheckboxList("listingOptions", "Options", $listingOptions);
    }

    /**
     * Form validation function
     * @param Form $form
     */
    public function formValidateValues($form){   
        $values = $form->getValues(TRUE);
        
        foreach ($values as $value){
            if (!isset($value) || (is_string($value) && strcmp($value, "") == 0)){
                $form->addError("Formulář nesmí obsahovat prázdné pole.");
            }
        }
    }
    
    /**
     * Performs value processing to later save in db
     * Used for, listing create & edit
     * 
     * @param array $values
     * @param bool $type - ifset means edit-listing
     * @return array
     */
    public function getProcValues($values, $type = NULL){
        //add listing author to values
        $values['author'] = $this->base->logn();

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

        unset($values["listingOptions"]);

        $imageLocations = array();
        $images = $values['image'];

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
            //do image stuff only if new images uploaded
            if (!empty($imageLocations)){
                $listingID = $this->base->sess("listing")->listingID;
                $existingImages = $this->listings->getListingImages($listingID);
                $values["n_img"] = serialize(array_merge($imageLocations, $existingImages));
            }
        }
        
        //remove postage options as they are processed separately
        foreach ($values as $key => $value) {
            if (strpos($key, "postage") !== FALSE || strpos($key, "pprice") !== FALSE) {
                unset($values[$key]);
            }
        }
        return $values;
    }

    /**
     * Sets listing session and performs
     * check if user is listing author to 
     * prevent form rendering and buying from myself.
     * 
     * @param int $id
     */
    public function sessNcheck($id){
        $ld = $this->listings->getActualListingValues($id);
        $la = $this->listings->getAuthor($id);
        $render = FALSE;
        
        if ($this->base->logn() != $la){
            $render = TRUE;
        }

        $this->base->sets("listing", array("listingDetails" => $ld,
            "render" => $render));
    }
    
    /**
     * Component name comparator called from template.
     * Serves rendering of delete postage action link.
     * 
     * @param string $name
     * @return boolean
     */
    public function compareComponentName($name){
        if (strpos($name, "postage") !== FALSE){    
            if (strpos($name, "add_postage") !== FALSE){
                return FALSE;
            }
            return TRUE;
            
        } else {
            return FALSE;
        }
    }
    
    /**
     * Compares form values
     * with db values and determines
     * what to save into db
     * 
     * @param array $toUpdate
     * @param array $fromDB
     * @return array
     */
    public function arrayToWrite($toUpdate, $fromDB){
        $arrayToWrite = array();
        $cnt = 0;

        foreach($fromDB as $option){
            if ($toUpdate['options'][$cnt] !== $option['option']){
                $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                $arrayToWrite[$cnt]['option'] = $toUpdate['options'][$cnt];
            }

            if ($toUpdate['prices'][$cnt] !== (string) $option['price']){
                $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                $arrayToWrite[$cnt]['price'] = $toUpdate['prices'][$cnt];
            }

            $cnt++; 
        }
        
        return $arrayToWrite;
    }
    
    /**
     * Listings:view paginated
     * feedback renderer
     * 
     * @param int $id listingID from URL
     * @param int $page actualPage from URL
     */
    public function drawPaginator($id, $page){
        if ($this->listings->hasFeedback($id)){            
            $pg = $this->base->paginatorSetup($page, 10);
            $data = $this->listings->getFeedback($id, $pg);
            $pgcount = $pg->getPageCount();
            $this->base->paginatorTemplate("feedback", $data, $pgcount, $page);
        }
    }
}