<?php

namespace App\Model;

use dibi;

/**
 * 
 * @what Listings data model class
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class Listings extends BaseModel {
        
    public function isVendor($login){
       $q = $this->valSelect("access_level", "users", "login", $login);
       return $this->checker($q, "vendor");
    }

    public function becomeVendor($login){        
        $this->updater("users", array('access_level' => 'vendor'), "login", $login);
    }
        
    public function createListing(array $values){
        
        $values["status"] = "disabled";
        
        dibi::insert('listings', $values)->execute();
        
        return dibi::getInsertId();
    }
    
    public function writeListingPostageOptions($listingID, array $postage){
        
        $postageLen = count($postage['options']);
        $listing = array();
        
        //assemble array with listingID duplicates
        for($i=0; $i<$postageLen; $i++){
            array_push($listing, $listingID);
        }
        
        //to correctly form argument array
        $a = array (
            "listing_id" => $listing,
            "option" => $postage['options'],
            "price" => $postage['prices']
        );

        //to pass it to db layer with %m multiple insert modifier
        dibi::query('INSERT INTO [postage] %m', $a);
    }
    
    public function updatePostageOptions(array $values){

        //decided not to use collision insert technique
        foreach ($values as $value){
            
            if (array_key_exists("option", $value)){                
                $this->updater("postage",array("option" => $value["option"]),
                        "postage_id", $value["id"]);
            }
            
            if (array_key_exists("price", $value)){
                $this->updater("postage",array("price" => $value["price"]),
                        "postage_id", $value['id']);
            }   
        }
    }
    
    public function getPostageOptions($id){
        return $this->valSelect("*", "postage", "listing_id", $id, TRUE);
    }
    
    public function verifyPostage($ids, $option, $price){
        $q = dibi::select('*')->from('postage')->where(array('option' => $option))
                ->where(array('price' => $price))->fetch();

        if ($q){
            if (in_array($q['postage_id'], $ids)){
                return TRUE;
            }
        }
 
        return FALSE;
    }
    
    public function deletePostageOption($id){
        return dibi::delete('postage')->where('postage_id = %i', $id)->execute();
    }
        
    public function getListings($author){
        return $this->valSelect(array("id", "product_name", "status"), 
                "listings", "author", $author, TRUE);
    }
    
    public function editListing($id, $values){
        return $this->updater("listings", $values, "id", $id);
    }
    
    public function deleteListing($id){
        return dibi::delete('listings')->where('id = %i', $id)->execute();
    }
    
    public function getActualListingValues($id){
        
        return $this->valSelect(array("id", "product_name", "product_type", 
            "product_desc", "price", "ships_from", "ships_to", "author"
            ,"MS", "FE"), "listings", "id", $id);
    }
    
    public function getAuthor($id){       
        return $this->valSelect("author", "listings", "id", $id);
    }
    
    public function isListingAuthor($id, $login){
        $listingAuthor = $this->getAuthor($id);
        return $this->checker($listingAuthor, $login);
    }
    
    public function getListingImages($id){
        return unserialize($this->valSelect("product_images", "listings", "id", $id));  
    }
    
    public function updateListingImages($id, $images){        
        $this->updater("listings", array("product_images" => $images), "id", $id);
    }
    
    public function setListingMainImage($id, $imgNum){
        $this->updater("listings", array('main_image' => $imgNum), "id", $id);
    }
    
    public function getListingMainImage($id){
        return $this->valSelect("main_image", "listings", "id", $id);   
    }

    public function getListingPrice($id){
        return $this->valSelect("price", "listings", "id", $id);  
    }
    
    public function enableListing($id){        
        return $this->updater("listings", array('status' => 'active'), "id", $id);
    }
    
    public function disableListing($id){       
        return $this->updater("listings", array("status" => "disabled"), "id", $id);
    }
    
    public function isListingActive($id){
        $q = $this->valSelect("status", "listings", "id", $id);     
        return $this->checker($q, "active");
    }
    
    public function isListingFE($id){
        $q = $this->valSelect("FE", "listings", "id", $id);
        return $this->checker($q, "yes");
    }
    
    public function isListingMultisig($id){
        $q = $this->valSelect("MS", "listings", "id", $id);
        return $this->checker($q, "yes");
    }
}