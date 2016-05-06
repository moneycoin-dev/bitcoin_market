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
       $q = $this->slect("access_level", "users", "login", $login);
       return $this->check($q, "vendor");
    }

    public function becomeVendor($login){        
        $this->upd("users", array('access_level' => 'vendor'), "login", $login);
    }
        
    public function createListing(array $values){
        $values["status"] = "disabled";
        return $this->nsrt("listings", $values, TRUE);
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
                $this->upd("postage",array("option" => $value["option"]),
                        "postage_id", $value["id"]);
            }
            
            if (array_key_exists("price", $value)){
                $this->upd("postage",array("price" => $value["price"]),
                        "postage_id", $value['id']);
            }   
        }
    }
    
    public function getPostageOptions($id){
        return $this->slect("*", "postage", "listing_id", $id, TRUE);
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
        return $this->slect(array("id", "product_name", "status"), 
                "listings", "author", $author, TRUE);
    }
    
    public function editListing($id, $values){
        return $this->upd("listings", $values, "id", $id);
    }
    
    public function deleteListing($id){
        return dibi::delete('listings')->where('id = %i', $id)->execute();
    }
    
    public function getActualListingValues($id){
        
        return $this->slect(array("id", "product_name", "product_type", 
            "product_desc", "price", "ships_from", "ships_to", "author"
            ,"MS", "FE"), "listings", "id", $id);
    }
    
    public function getAuthor($id){       
        return $this->slect("author", "listings", "id", $id);
    }
    
    public function isListingAuthor($id, $login){
        $listingAuthor = $this->getAuthor($id);
        return $this->check($listingAuthor, $login);
    }
    
    public function getListingImages($id){
        return unserialize($this->slect("product_images", "listings", "id", $id));  
    }
    
    public function updateListingImages($id, $images){        
        $this->upd("listings", array("product_images" => $images), "id", $id);
    }
    
    public function setListingMainImage($id, $imgNum){
        $this->upd("listings", array('main_image' => $imgNum), "id", $id);
    }
    
    public function getListingMainImage($id){
        return $this->slect("main_image", "listings", "id", $id);   
    }

    public function getListingPrice($id){
        return $this->slect("price", "listings", "id", $id);  
    }
    
    public function enableListing($id){        
        return $this->upd("listings", array('status' => 'active'), "id", $id);
    }
    
    public function disableListing($id){       
        return $this->upd("listings", array("status" => "disabled"), "id", $id);
    }
    
    public function isListingActive($id){
        $q = $this->slect("status", "listings", "id", $id);     
        return $this->check($q, "active");
    }
    
    public function isListingFE($id){
        $q = $this->slect("FE", "listings", "id", $id);
        return $this->check($q, "yes");
    }
    
    public function isListingMultisig($id){
        $q = $this->slect("MS", "listings", "id", $id);
        return $this->check($q, "yes");
    }
}