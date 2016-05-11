<?php

namespace App\Helpers;

use App\Model\UserManager;
use App\Model\Listings;

/**
 * 
 * @what Helper class for Listings Presenter
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class ListingsHelper extends BaseHelper {

    protected $listings, $userManager;

    public function injectListings(Listings $ls){
            $this->listings = $ls;
    }

    public function injectUserManager(UserManager $um){
            $this->userManager = $um;
    }

    public function imgUpload($images, $form){
        
        foreach($images as $image){
        
            if ($image->isOk() && $image->isImage()){
                $filesPath = getcwd() . "/userfiles/" ;
                $username = $this->logn();
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

    public function returnPostageArray($values, $flag = NULL){
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

    public function fillForm($form , $values){
        
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

    public function constructCheckboxList($form){
        $listingOptions = array("ms" => "Multisig");

        if($this->userManager->hasFEallowed($this->logn())){
            $listingOptions["fe"] = "Finalize Early";
        }
        
        return $form->addCheckboxList("listingOptions", "Options", $listingOptions);
    }

    public function formValidateValues($form){
        
        $values = $form->getValues(TRUE);
        
        foreach ($values as $value){
            if (!isset($value) || (is_string($value) && strcmp($value, "") == 0)){
                $form->addError("Formulář nesmí obsahovat prázdné pole.");
            }
        }
    }

    public function getProcValues($values, $type = NULL){
        ///performs value processing to later save in db///  
        //add listing author to values
        $values['author'] = $this->logn();

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
                $listingID = $this->sess("listing")->listingID;
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
        
        if ($this->logn() != $la){
            $render = TRUE;
        }

        $this->sets("listing", array("listingDetails" => $ld,
            "render" => $render));
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
    
    public function arrayToWrite($toUpdate, $fromDB){
        
    //assemble array with new postage values and database ids to edit
        $arrayToWrite = array();
        $cnt = 0;

        foreach($fromDB as $option){
            if ($toUpdate['options'][$cnt] !== $option['option']){
                $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                $arrayToWrite[$cnt]['option'] = $toUpdate['options'][$cnt];
            }

            if ($postageToUpdate['prices'][$cnt] !== (string) $option['price']){
                $arrayToWrite[$cnt]['id'] = $option['postage_id'];
                $arrayToWrite[$cnt]['price'] = $toUpdate['prices'][$cnt];
            }

            $cnt++; 
        }
        
        return $arrayToWrite;
    }
}